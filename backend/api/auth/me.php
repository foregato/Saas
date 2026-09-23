<?php

use App\Config\Database;
use App\Helpers\Response;
use App\Middleware\AuthMiddleware;

$context = AuthMiddleware::authenticate();

$pdo = Database::connection();
$stmt = $pdo->prepare(
    'SELECT u.id, u.nome, u.email, u.telefone, u.role, u.tenant_id,
            c.razao_social, c.nome_fantasia, c.cnpj, c.onboarding_completo
     FROM users u JOIN companies c ON c.id = u.tenant_id
     WHERE u.id = ? AND u.tenant_id = ?'
);
$stmt->execute([$context['user_id'], $context['tenant_id']]);
$user = $stmt->fetch();

if (!$user) {
    Response::error('Usuário não encontrado.', 404);
}

Response::success(['user' => $user]);
