<?php

namespace App\Auth;

use App\Config\Database;

class PasswordResetService
{
    private const TOKEN_TTL_MINUTES = 60;

    /**
     * Gera um token de recuperação. Retorna o token bruto apenas para fins de
     * envio por e-mail — a rota nunca deve devolver isso na resposta em produção
     * (aqui o e-mail ainda não está integrado, então o token é logado).
     */
    public static function requestReset(string $email): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id, tenant_id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Resposta sempre genérica — não revelar se o e-mail existe.
        if (!$user) {
            return;
        }

        $raw = bin2hex(random_bytes(32));
        $hash = hash('sha256', $raw);
        $expiresAt = (new \DateTime())->modify('+' . self::TOKEN_TTL_MINUTES . ' minutes')->format('Y-m-d H:i:s');

        $pdo->prepare(
            'INSERT INTO password_resets (tenant_id, user_id, token_hash, expires_at) VALUES (?, ?, ?, ?)'
        )->execute([$user['tenant_id'], $user['id'], $hash, $expiresAt]);

        // TODO (fase de integrações): enviar $raw por e-mail via serviço de envio.
        // Por enquanto, registrado em log para ambiente de desenvolvimento.
        error_log("Password reset token for {$email}: {$raw}");
    }

    public static function reset(string $rawToken, string $newPassword): bool
    {
        $pdo = Database::connection();
        $hash = hash('sha256', $rawToken);

        $stmt = $pdo->prepare(
            'SELECT id, user_id FROM password_resets
             WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()'
        );
        $stmt->execute([$hash]);
        $row = $stmt->fetch();

        if (!$row) {
            return false;
        }

        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($newPassword, PASSWORD_DEFAULT), $row['user_id']]);
            $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')
                ->execute([$row['id']]);
            // Revoga todos os tokens de sessão ativos por segurança.
            $pdo->prepare('UPDATE auth_tokens SET revoked_at = NOW() WHERE user_id = ? AND revoked_at IS NULL')
                ->execute([$row['user_id']]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('Password reset error: ' . $e->getMessage());
            return false;
        }

        return true;
    }
}
