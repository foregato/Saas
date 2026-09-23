<?php

namespace App\Services;

use App\Config\Database;
use App\Helpers\Period;

class DashboardService
{
    public static function summary(int $tenantId, string $period, ?string $customStart, ?string $customEnd): array
    {
        $pdo = Database::connection();
        $range = Period::resolve($period, $customStart, $customEnd);

        $current = self::periodTotals($pdo, $tenantId, $range['start'], $range['end']);
        $previous = self::periodTotals($pdo, $tenantId, $range['prev_start'], $range['prev_end']);

        return [
            'periodo' => [
                'inicio' => $range['start'],
                'fim' => $range['end'],
            ],
            'faturamento' => $current['faturamento'],
            'despesas' => $current['despesas'],
            'resultado' => $current['faturamento'] - $current['despesas'],
            'quantidade_vendas' => $current['quantidade_vendas'],
            'ticket_medio' => $current['ticket_medio'],
            'comparacao_periodo_anterior' => [
                'faturamento' => $previous['faturamento'],
                'despesas' => $previous['despesas'],
                'resultado' => $previous['faturamento'] - $previous['despesas'],
            ],
            'contas_a_receber' => self::outstanding($pdo, $tenantId, 'entrada'),
            'contas_a_pagar' => self::outstanding($pdo, $tenantId, 'saida'),
            'saldo_registrado' => self::runningBalance($pdo, $tenantId),
            'atencao' => self::attentionItems($pdo, $tenantId),
        ];
    }

    private static function periodTotals(\PDO $pdo, int $tenantId, string $start, string $end): array
    {
        $stmt = $pdo->prepare(
            'SELECT
                COALESCE(SUM(CASE WHEN tipo = "entrada" AND status = "recebido" THEN valor ELSE 0 END), 0) AS faturamento,
                COALESCE(SUM(CASE WHEN tipo = "saida" AND status = "pago" THEN valor ELSE 0 END), 0) AS despesas
             FROM financial_entries
             WHERE tenant_id = ? AND data BETWEEN ? AND ?'
        );
        $stmt->execute([$tenantId, $start, $end]);
        $totals = $stmt->fetch();

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS quantidade, COALESCE(AVG(valor_final), 0) AS ticket_medio
             FROM sales
             WHERE tenant_id = ? AND status = "concluida" AND data BETWEEN ? AND ?'
        );
        $stmt->execute([$tenantId, $start, $end]);
        $sales = $stmt->fetch();

        return [
            'faturamento' => (float) $totals['faturamento'],
            'despesas' => (float) $totals['despesas'],
            'quantidade_vendas' => (int) $sales['quantidade'],
            'ticket_medio' => round((float) $sales['ticket_medio'], 2),
        ];
    }

    /** Total em aberto (pendente/atrasado), sem limite de período — é uma foto do momento atual. */
    private static function outstanding(\PDO $pdo, int $tenantId, string $tipo): float
    {
        $stmt = $pdo->prepare(
            'SELECT COALESCE(SUM(valor), 0) AS total
             FROM financial_entries
             WHERE tenant_id = ? AND tipo = ? AND status IN ("pendente", "atrasado")'
        );
        $stmt->execute([$tenantId, $tipo]);
        return (float) $stmt->fetch()['total'];
    }

    /** Recebido - pago, desde o início (saldo acumulado da empresa). */
    private static function runningBalance(\PDO $pdo, int $tenantId): float
    {
        $stmt = $pdo->prepare(
            'SELECT
                COALESCE(SUM(CASE WHEN tipo = "entrada" AND status = "recebido" THEN valor ELSE 0 END), 0)
              - COALESCE(SUM(CASE WHEN tipo = "saida" AND status = "pago" THEN valor ELSE 0 END), 0) AS saldo
             FROM financial_entries
             WHERE tenant_id = ?'
        );
        $stmt->execute([$tenantId]);
        return (float) $stmt->fetch()['saldo'];
    }

    /** Lista de itens que precisam de atenção: vencimentos próximos/atrasados e estoque baixo. */
    private static function attentionItems(\PDO $pdo, int $tenantId): array
    {
        $items = [];

        // Contas (financeiro) atrasadas ou vencendo nos próximos 7 dias.
        $stmt = $pdo->prepare(
            'SELECT descricao, valor, data, tipo, status
             FROM financial_entries
             WHERE tenant_id = ?
               AND status IN ("pendente", "atrasado")
               AND data <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
             ORDER BY data ASC
             LIMIT 10'
        );
        $stmt->execute([$tenantId]);
        foreach ($stmt->fetchAll() as $row) {
            $vencida = $row['status'] === 'atrasado' || $row['data'] < date('Y-m-d');
            $items[] = [
                'tipo' => 'conta_' . $row['tipo'],
                'titulo' => $row['descricao'],
                'data' => $row['data'],
                'valor' => (float) $row['valor'],
                'urgencia' => $vencida ? 'atrasado' : 'proximo',
            ];
        }

        // Obrigações não pagas, vencendo em até 14 dias ou já atrasadas.
        $stmt = $pdo->prepare(
            'SELECT nome, vencimento
             FROM obligations
             WHERE tenant_id = ? AND paga = 0
               AND vencimento <= DATE_ADD(CURDATE(), INTERVAL 14 DAY)
             ORDER BY vencimento ASC
             LIMIT 10'
        );
        $stmt->execute([$tenantId]);
        foreach ($stmt->fetchAll() as $row) {
            $items[] = [
                'tipo' => 'obrigacao',
                'titulo' => $row['nome'],
                'data' => $row['vencimento'],
                'valor' => null,
                'urgencia' => $row['vencimento'] < date('Y-m-d') ? 'atrasado' : 'proximo',
            ];
        }

        // Documentos com validade próxima (30 dias) ou vencida.
        $stmt = $pdo->prepare(
            'SELECT nome, validade
             FROM documents
             WHERE tenant_id = ? AND validade IS NOT NULL
               AND validade <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
             ORDER BY validade ASC
             LIMIT 10'
        );
        $stmt->execute([$tenantId]);
        foreach ($stmt->fetchAll() as $row) {
            $items[] = [
                'tipo' => 'documento',
                'titulo' => $row['nome'],
                'data' => $row['validade'],
                'valor' => null,
                'urgencia' => $row['validade'] < date('Y-m-d') ? 'atrasado' : 'proximo',
            ];
        }

        // Estoque baixo.
        $stmt = $pdo->prepare(
            'SELECT nome, estoque, estoque_minimo
             FROM products
             WHERE tenant_id = ? AND status = "ativo" AND estoque <= estoque_minimo
             ORDER BY nome ASC
             LIMIT 10'
        );
        $stmt->execute([$tenantId]);
        foreach ($stmt->fetchAll() as $row) {
            $items[] = [
                'tipo' => 'estoque_baixo',
                'titulo' => $row['nome'] . ' — estoque: ' . $row['estoque'],
                'data' => null,
                'valor' => null,
                'urgencia' => 'atencao',
            ];
        }

        return $items;
    }
}
