<?php

namespace App\Auth;

use App\Config\Database;
use PDO;

class AuthService
{
    private const TOKEN_TTL_DAYS = 30;

    public static function register(string $nome, string $email, string $telefone, string $password): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['error' => 'Este e-mail já está cadastrado.'];
        }

        $pdo->beginTransaction();
        try {
            // Empresa "placeholder" criada no cadastro; completada no onboarding.
            $pdo->prepare(
                'INSERT INTO companies (cnpj, razao_social, onboarding_completo)
                 VALUES (?, ?, 0)'
            )->execute([self::placeholderCnpj(), 'Empresa de ' . $nome]);
            $tenantId = (int) $pdo->lastInsertId();

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare(
                'INSERT INTO users (tenant_id, nome, email, telefone, password_hash, role)
                 VALUES (?, ?, ?, ?, ?, "owner")'
            )->execute([$tenantId, $nome, $email, $telefone, $hash]);
            $userId = (int) $pdo->lastInsertId();

            // Assinatura trial no plano gratuito.
            $planStmt = $pdo->prepare('SELECT id FROM plans WHERE codigo = "free" LIMIT 1');
            $planStmt->execute();
            $plan = $planStmt->fetch();
            if ($plan) {
                $pdo->prepare(
                    'INSERT INTO subscriptions (tenant_id, plan_id, status, inicio)
                     VALUES (?, ?, "trial", CURDATE())'
                )->execute([$tenantId, $plan['id']]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('Register error: ' . $e->getMessage());
            return ['error' => 'Não foi possível concluir o cadastro.'];
        }

        $token = self::issueToken($tenantId, $userId);

        return [
            'user' => self::publicUser($userId),
            'token' => $token,
        ];
    }

    public static function login(string $email, string $password): array
    {
        $pdo = Database::connection();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        if (self::isRateLimited($email)) {
            return ['error' => 'Muitas tentativas de login. Tente novamente em alguns minutos.'];
        }

        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $pdo->prepare('INSERT INTO login_attempts (email, ip_address, success) VALUES (?, ?, 0)')
                ->execute([$email, $ip]);
            return ['error' => 'E-mail ou senha inválidos.'];
        }

        $pdo->prepare('INSERT INTO login_attempts (email, ip_address, success) VALUES (?, ?, 1)')
            ->execute([$email, $ip]);

        $token = self::issueToken((int) $user['tenant_id'], (int) $user['id']);

        return [
            'user' => self::publicUser((int) $user['id']),
            'token' => $token,
        ];
    }

    public static function logout(string $rawToken): void
    {
        $pdo = Database::connection();
        $hash = hash('sha256', $rawToken);
        $pdo->prepare('UPDATE auth_tokens SET revoked_at = NOW() WHERE token_hash = ?')->execute([$hash]);
    }

    /** Retorna ['user_id' => int, 'tenant_id' => int] ou null se inválido/expirado. */
    public static function validateToken(string $rawToken): ?array
    {
        $pdo = Database::connection();
        $hash = hash('sha256', $rawToken);

        $stmt = $pdo->prepare(
            'SELECT user_id, tenant_id FROM auth_tokens
             WHERE token_hash = ? AND revoked_at IS NULL AND expires_at > NOW()'
        );
        $stmt->execute([$hash]);
        $row = $stmt->fetch();

        return $row ? ['user_id' => (int) $row['user_id'], 'tenant_id' => (int) $row['tenant_id']] : null;
    }

    public static function isPlatformAdmin(int $userId): bool
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT is_platform_admin FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row && (int) $row['is_platform_admin'] === 1;
    }

    private static function issueToken(int $tenantId, int $userId): string
    {
        $pdo = Database::connection();
        $raw = bin2hex(random_bytes(32));
        $hash = hash('sha256', $raw);
        $expiresAt = (new \DateTime())->modify('+' . self::TOKEN_TTL_DAYS . ' days')->format('Y-m-d H:i:s');

        $pdo->prepare(
            'INSERT INTO auth_tokens (tenant_id, user_id, token_hash, user_agent, ip_address, expires_at)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([
            $tenantId,
            $userId,
            $hash,
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            $_SERVER['REMOTE_ADDR'] ?? null,
            $expiresAt,
        ]);

        return $raw;
    }

    private static function publicUser(int $userId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT u.id, u.nome, u.email, u.telefone, u.role, u.tenant_id,
                    c.onboarding_completo, c.razao_social, c.nome_fantasia
             FROM users u JOIN companies c ON c.id = u.tenant_id
             WHERE u.id = ?'
        );
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: [];
    }

    private static function isRateLimited(string $email): bool
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS attempts FROM login_attempts
             WHERE email = ? AND success = 0 AND created_at > (NOW() - INTERVAL 15 MINUTE)'
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row && (int) $row['attempts'] >= 10;
    }

    private static function placeholderCnpj(): string
    {
        // CNPJ único e temporário até o onboarding preencher o real.
        return 'PENDENTE-' . bin2hex(random_bytes(6));
    }
}
