<?php

namespace App\Middleware;

use App\Auth\AuthService;
use App\Helpers\Request;
use App\Helpers\Response;

class AuthMiddleware
{
    /**
     * Garante que a requisição tem um token válido.
     * Retorna ['user_id' => int, 'tenant_id' => int] — toda query subsequente
     * DEVE filtrar por tenant_id. Isso é aplicado no backend, nunca só no frontend.
     */
    public static function authenticate(): array
    {
        $token = Request::bearerToken();

        if (!$token) {
            Response::error('Não autenticado.', 401);
        }

        $context = AuthService::validateToken($token);

        if (!$context) {
            Response::error('Sessão inválida ou expirada.', 401);
        }

        return $context;
    }

    public static function requirePlatformAdmin(int $userId): void
    {
        if (!AuthService::isPlatformAdmin($userId)) {
            Response::error('Acesso restrito ao administrador da plataforma.', 403);
        }
    }
}
