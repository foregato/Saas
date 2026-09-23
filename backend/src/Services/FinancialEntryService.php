<?php

namespace App\Services;

use App\Config\Database;
use PDO;

class FinancialEntryService
{
    private const TIPOS = ['entrada', 'saida'];
    private const STATUS_BY_TIPO = [
        'entrada' => ['recebido', 'pendente', 'atrasado', 'cancelado'],
        'saida' => ['pago', 'pendente', 'atrasado', 'cancelado'],
    ];
    private const RECORRENCIAS = ['nenhuma', 'mensal', 'semanal', 'anual'];
    private const DEFAULT_PER_PAGE = 20;
    private const MAX_PER_PAGE = 100;

    /**
     * Lista lançamentos do tenant com filtros opcionais.
     * $filters: start, end (sobre "data"), tipo, category_id, status, page, per_page
     */
    public static function list(int $tenantId, array $filters): array
    {
        $pdo = Database::connection();

        $where = ['tenant_id = ?'];
        $params = [$tenantId];

        if (!empty($filters['start'])) {
            $where[] = 'data >= ?';
            $params[] = $filters['start'];
        }
        if (!empty($filters['end'])) {
            $where[] = 'data <= ?';
            $params[] = $filters['end'];
        }
        if (!empty($filters['tipo']) && in_array($filters['tipo'], self::TIPOS, true)) {
            $where[] = 'tipo = ?';
            $params[] = $filters['tipo'];
        }
        if (!empty($filters['category_id'])) {
            $where[] = 'category_id = ?';
            $params[] = (int) $filters['category_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
        }

        $perPage = min(self::MAX_PER_PAGE, max(1, (int) ($filters['per_page'] ?? self::DEFAULT_PER_PAGE)));
        $page = max(1, (int) ($filters['page'] ?? 1));
        $offset = ($page - 1) * $perPage;

        $whereSql = implode(' AND ', $where);

        $countStmt = $pdo->prepare("SELECT COUNT(*) AS total FROM financial_entries WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetch()['total'];

        $sql = "SELECT e.*, c.nome AS categoria_nome
                FROM financial_entries e
                LEFT JOIN financial_categories c ON c.id = e.category_id
                WHERE {$whereSql}
                ORDER BY e.data DESC, e.id DESC
                LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return [
            'entries' => $stmt->fetchAll(),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => (int) ceil($total / $perPage),
            ],
        ];
    }

    public static function create(int $tenantId, array $input): array
    {
        $validated = self::validate($tenantId, $input, null);
        if (isset($validated['error'])) {
            return $validated;
        }
        $data = $validated['data'];

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'INSERT INTO financial_entries
                    (tenant_id, tipo, descricao, valor, data, category_id, forma_pagamento, status, recorrencia, observacao)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $tenantId, $data['tipo'], $data['descricao'], $data['valor'], $data['data'],
                $data['category_id'], $data['forma_pagamento'], $data['status'], $data['recorrencia'], $data['observacao'],
            ]);
            $id = (int) $pdo->lastInsertId();

            self::syncAccountRow($pdo, $tenantId, $id, $data['tipo'], $data['status'], $data['data']);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('FinancialEntry create error: ' . $e->getMessage());
            return ['error' => 'Não foi possível salvar o lançamento.'];
        }

        return ['entry' => self::find($tenantId, $id)];
    }

    public static function update(int $tenantId, int $id, array $input): array
    {
        $existing = self::find($tenantId, $id);
        if (!$existing) {
            return ['error' => 'Lançamento não encontrado.'];
        }

        // tipo não pode mudar depois de criado (evita inconsistência com categoria/status já escolhidos).
        $input['tipo'] = $existing['tipo'];

        $validated = self::validate($tenantId, $input, $id);
        if (isset($validated['error'])) {
            return $validated;
        }
        $data = $validated['data'];

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'UPDATE financial_entries SET
                    descricao = ?, valor = ?, data = ?, category_id = ?, forma_pagamento = ?,
                    status = ?, recorrencia = ?, observacao = ?
                 WHERE id = ? AND tenant_id = ?'
            )->execute([
                $data['descricao'], $data['valor'], $data['data'], $data['category_id'], $data['forma_pagamento'],
                $data['status'], $data['recorrencia'], $data['observacao'], $id, $tenantId,
            ]);

            self::syncAccountRow($pdo, $tenantId, $id, $data['tipo'], $data['status'], $data['data']);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('FinancialEntry update error: ' . $e->getMessage());
            return ['error' => 'Não foi possível atualizar o lançamento.'];
        }

        return ['entry' => self::find($tenantId, $id)];
    }

    /** "Exclusão" preserva histórico: o lançamento é marcado como cancelado, nunca apagado de fato. */
    public static function cancel(int $tenantId, int $id): array
    {
        $existing = self::find($tenantId, $id);
        if (!$existing) {
            return ['error' => 'Lançamento não encontrado.'];
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE financial_entries SET status = "cancelado" WHERE id = ? AND tenant_id = ?')
                ->execute([$id, $tenantId]);
            self::syncAccountRow($pdo, $tenantId, $id, $existing['tipo'], 'cancelado', $existing['data']);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('FinancialEntry cancel error: ' . $e->getMessage());
            return ['error' => 'Não foi possível cancelar o lançamento.'];
        }

        return ['entry' => self::find($tenantId, $id)];
    }

    private static function validate(int $tenantId, array $input, ?int $ignoreId): array
    {
        $tipo = (string) ($input['tipo'] ?? '');
        if (!in_array($tipo, self::TIPOS, true)) {
            return ['error' => 'Tipo deve ser "entrada" ou "saida".'];
        }

        $descricao = trim((string) ($input['descricao'] ?? ''));
        if ($descricao === '') {
            return ['error' => 'A descrição é obrigatória.'];
        }

        $valor = (float) ($input['valor'] ?? 0);
        if ($valor <= 0) {
            return ['error' => 'O valor deve ser maior que zero.'];
        }

        $data = (string) ($input['data'] ?? '');
        $d = \DateTime::createFromFormat('Y-m-d', $data);
        if (!$d || $d->format('Y-m-d') !== $data) {
            return ['error' => 'Data inválida. Use o formato AAAA-MM-DD.'];
        }

        $status = (string) ($input['status'] ?? '');
        if (!in_array($status, self::STATUS_BY_TIPO[$tipo], true)) {
            $opcoes = implode(', ', self::STATUS_BY_TIPO[$tipo]);
            return ['error' => "Status inválido para {$tipo}. Use: {$opcoes}."];
        }

        $recorrencia = (string) ($input['recorrencia'] ?? 'nenhuma');
        if (!in_array($recorrencia, self::RECORRENCIAS, true)) {
            $recorrencia = 'nenhuma';
        }

        $categoryId = $input['category_id'] ?? null;
        if ($categoryId !== null) {
            $categoryId = (int) $categoryId;
            $pdo = Database::connection();
            $stmt = $pdo->prepare('SELECT id FROM financial_categories WHERE id = ? AND tenant_id = ? AND tipo = ?');
            $stmt->execute([$categoryId, $tenantId, $tipo]);
            if (!$stmt->fetch()) {
                return ['error' => 'Categoria inválida para este tipo de lançamento.'];
            }
        }

        return [
            'data' => [
                'tipo' => $tipo,
                'descricao' => $descricao,
                'valor' => round($valor, 2),
                'data' => $data,
                'category_id' => $categoryId,
                'forma_pagamento' => trim((string) ($input['forma_pagamento'] ?? '')) ?: null,
                'status' => $status,
                'recorrencia' => $recorrencia,
                'observacao' => trim((string) ($input['observacao'] ?? '')) ?: null,
            ],
        ];
    }

    /**
     * Mantém accounts_receivable/accounts_payable em sincronia com o status do lançamento.
     * Enquanto o lançamento estiver pendente/atrasado, existe uma linha de vencimento
     * correspondente; quando é quitado ou cancelado, a linha é removida (o lançamento em
     * si continua existindo em financial_entries para histórico).
     *
     * Decisão da Fase 3: "vencimento" usa a mesma data do lançamento (não existe ainda um
     * campo de data de vencimento separado da data de lançamento) — documentado no
     * CONTEXTO_PROJETO.md.
     */
    private static function syncAccountRow(PDO $pdo, int $tenantId, int $entryId, string $tipo, string $status, string $data): void
    {
        $table = $tipo === 'entrada' ? 'accounts_receivable' : 'accounts_payable';
        $precisaVencimento = in_array($status, ['pendente', 'atrasado'], true);

        $stmt = $pdo->prepare("SELECT id FROM {$table} WHERE financial_entry_id = ? AND tenant_id = ?");
        $stmt->execute([$entryId, $tenantId]);
        $existing = $stmt->fetch();

        if ($precisaVencimento) {
            if ($existing) {
                $pdo->prepare("UPDATE {$table} SET vencimento = ?, pago_em = NULL WHERE id = ?")
                    ->execute([$data, $existing['id']]);
            } else {
                $pdo->prepare(
                    "INSERT INTO {$table} (tenant_id, financial_entry_id, vencimento) VALUES (?, ?, ?)"
                )->execute([$tenantId, $entryId, $data]);
            }
        } elseif ($existing) {
            $pagoEm = in_array($status, ['recebido', 'pago'], true) ? date('Y-m-d') : null;
            if ($pagoEm) {
                $pdo->prepare("UPDATE {$table} SET pago_em = ? WHERE id = ?")->execute([$pagoEm, $existing['id']]);
            } else {
                // cancelado: remove o registro de vencimento em aberto.
                $pdo->prepare("DELETE FROM {$table} WHERE id = ?")->execute([$existing['id']]);
            }
        }
    }

    private static function find(int $tenantId, int $id): ?array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT e.*, c.nome AS categoria_nome
             FROM financial_entries e
             LEFT JOIN financial_categories c ON c.id = e.category_id
             WHERE e.id = ? AND e.tenant_id = ?'
        );
        $stmt->execute([$id, $tenantId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
