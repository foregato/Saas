<?php

namespace App\Services;

use App\Config\Database;

class FinancialCategoryService
{
    private const TIPOS = ['entrada', 'saida'];

    public static function list(int $tenantId, ?string $tipo): array
    {
        $pdo = Database::connection();

        if ($tipo !== null && in_array($tipo, self::TIPOS, true)) {
            $stmt = $pdo->prepare(
                'SELECT * FROM financial_categories WHERE tenant_id = ? AND tipo = ? ORDER BY nome'
            );
            $stmt->execute([$tenantId, $tipo]);
        } else {
            $stmt = $pdo->prepare(
                'SELECT * FROM financial_categories WHERE tenant_id = ? ORDER BY tipo, nome'
            );
            $stmt->execute([$tenantId]);
        }

        return $stmt->fetchAll();
    }

    public static function create(int $tenantId, array $input): array
    {
        $nome = trim((string) ($input['nome'] ?? ''));
        $tipo = (string) ($input['tipo'] ?? '');

        if ($nome === '') {
            return ['error' => 'O nome da categoria é obrigatório.'];
        }
        if (!in_array($tipo, self::TIPOS, true)) {
            return ['error' => 'Tipo deve ser "entrada" ou "saida".'];
        }

        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT id FROM financial_categories WHERE tenant_id = ? AND tipo = ? AND nome = ?'
        );
        $stmt->execute([$tenantId, $tipo, $nome]);
        if ($stmt->fetch()) {
            return ['error' => 'Já existe uma categoria com esse nome para esse tipo.'];
        }

        $pdo->prepare(
            'INSERT INTO financial_categories (tenant_id, nome, tipo) VALUES (?, ?, ?)'
        )->execute([$tenantId, $nome, $tipo]);

        return ['category' => self::find($tenantId, (int) $pdo->lastInsertId())];
    }

    public static function update(int $tenantId, int $id, array $input): array
    {
        $category = self::find($tenantId, $id);
        if (!$category) {
            return ['error' => 'Categoria não encontrada.'];
        }

        $nome = trim((string) ($input['nome'] ?? $category['nome']));
        if ($nome === '') {
            return ['error' => 'O nome da categoria é obrigatório.'];
        }

        $pdo = Database::connection();
        $pdo->prepare('UPDATE financial_categories SET nome = ? WHERE id = ? AND tenant_id = ?')
            ->execute([$nome, $id, $tenantId]);

        return ['category' => self::find($tenantId, $id)];
    }

    public static function delete(int $tenantId, int $id): array
    {
        $category = self::find($tenantId, $id);
        if (!$category) {
            return ['error' => 'Categoria não encontrada.'];
        }

        // Lançamentos que usam essa categoria ficam com category_id NULL (FK ON DELETE SET NULL) —
        // o histórico financeiro não é perdido, só desvinculado da categoria.
        $pdo = Database::connection();
        $pdo->prepare('DELETE FROM financial_categories WHERE id = ? AND tenant_id = ?')
            ->execute([$id, $tenantId]);

        return ['deleted' => true];
    }

    private static function find(int $tenantId, int $id): ?array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM financial_categories WHERE id = ? AND tenant_id = ?');
        $stmt->execute([$id, $tenantId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
