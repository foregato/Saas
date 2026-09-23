<?php

namespace App\Services;

use App\Config\Database;
use App\Helpers\Cnpj;

class CompanyService
{
    private const REQUIRED_FIELDS = ['cnpj', 'razao_social'];

    /**
     * Salva os dados de onboarding da empresa do tenant autenticado.
     * Retorna ['error' => string] em caso de falha de validação/negócio,
     * ou ['company' => array] com os dados atualizados.
     */
    public static function saveOnboarding(int $tenantId, array $input): array
    {
        foreach (self::REQUIRED_FIELDS as $field) {
            if (trim((string) ($input[$field] ?? '')) === '') {
                return ['error' => "O campo \"{$field}\" é obrigatório."];
            }
        }

        $cnpjDigits = Cnpj::digitsOnly((string) $input['cnpj']);
        if (!Cnpj::isValid($cnpjDigits)) {
            return ['error' => 'CNPJ inválido. Confira os números digitados.'];
        }

        $pdo = Database::connection();

        // Garante que o CNPJ não pertence a outro tenant já cadastrado.
        $stmt = $pdo->prepare('SELECT id FROM companies WHERE cnpj = ? AND id != ?');
        $stmt->execute([$cnpjDigits, $tenantId]);
        if ($stmt->fetch()) {
            return ['error' => 'Este CNPJ já está cadastrado em outra conta.'];
        }

        $fields = [
            'cnpj' => $cnpjDigits,
            'razao_social' => trim((string) $input['razao_social']),
            'nome_fantasia' => trim((string) ($input['nome_fantasia'] ?? '')) ?: null,
            'cnae' => trim((string) ($input['cnae'] ?? '')) ?: null,
            'endereco' => trim((string) ($input['endereco'] ?? '')) ?: null,
            'cidade' => trim((string) ($input['cidade'] ?? '')) ?: null,
            'estado' => strtoupper(trim((string) ($input['estado'] ?? ''))) ?: null,
            'telefone' => trim((string) ($input['telefone'] ?? '')) ?: null,
            'email' => trim((string) ($input['email'] ?? '')) ?: null,
        ];

        if ($fields['estado'] !== null && strlen($fields['estado']) !== 2) {
            return ['error' => 'Estado deve ser a sigla com 2 letras (ex: SP).'];
        }
        if ($fields['email'] !== null && !filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
            return ['error' => 'E-mail da empresa inválido.'];
        }

        $pdo->prepare(
            'UPDATE companies SET
                cnpj = ?, razao_social = ?, nome_fantasia = ?, cnae = ?,
                endereco = ?, cidade = ?, estado = ?, telefone = ?, email = ?,
                onboarding_completo = 1
             WHERE id = ?'
        )->execute([
            $fields['cnpj'], $fields['razao_social'], $fields['nome_fantasia'], $fields['cnae'],
            $fields['endereco'], $fields['cidade'], $fields['estado'], $fields['telefone'], $fields['email'],
            $tenantId,
        ]);

        $stmt = $pdo->prepare('SELECT * FROM companies WHERE id = ?');
        $stmt->execute([$tenantId]);

        return ['company' => $stmt->fetch()];
    }
}
