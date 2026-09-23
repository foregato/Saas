<?php

declare(strict_types=1);

require __DIR__ . '/../src/autoload.php';

use App\Config\Env;
use App\Helpers\Response;

Env::load(__DIR__ . '/../.env');

// ---------------------------------------------------------------------
// CORS — ajustar FRONTEND_URL no .env para o domínio de produção.
// ---------------------------------------------------------------------
$allowedOrigin = Env::get('FRONTEND_URL', 'http://localhost:5173');
header("Access-Control-Allow-Origin: {$allowedOrigin}");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Erros nunca devem vazar stack trace para o cliente.
set_exception_handler(function (Throwable $e) {
    error_log($e->getMessage() . "\n" . $e->getTraceAsString());
    Response::error('Erro interno do servidor.', 500);
});

// ---------------------------------------------------------------------
// Roteamento simples baseado em path (sem framework, compatível com
// hospedagem compartilhada / mod_rewrite via .htaccess).
// ---------------------------------------------------------------------
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = preg_replace('#^/api#', '', $uri ?? '');
$method = $_SERVER['REQUEST_METHOD'];

$routes = [
    'POST /auth/register' => __DIR__ . '/../api/auth/register.php',
    'POST /auth/login' => __DIR__ . '/../api/auth/login.php',
    'POST /auth/logout' => __DIR__ . '/../api/auth/logout.php',
    'POST /auth/forgot-password' => __DIR__ . '/../api/auth/forgot-password.php',
    'POST /auth/reset-password' => __DIR__ . '/../api/auth/reset-password.php',
    'GET /auth/me' => __DIR__ . '/../api/auth/me.php',

    'POST /companies' => __DIR__ . '/../api/companies/update.php',

    'GET /dashboard' => __DIR__ . '/../api/dashboard/summary.php',

    'GET /financial/categories' => __DIR__ . '/../api/financial/categories/list.php',
    'POST /financial/categories' => __DIR__ . '/../api/financial/categories/create.php',
    'PUT /financial/categories' => __DIR__ . '/../api/financial/categories/update.php',
    'DELETE /financial/categories' => __DIR__ . '/../api/financial/categories/delete.php',

    'GET /financial/entries' => __DIR__ . '/../api/financial/entries/list.php',
    'POST /financial/entries' => __DIR__ . '/../api/financial/entries/create.php',
    'PUT /financial/entries' => __DIR__ . '/../api/financial/entries/update.php',
    'DELETE /financial/entries' => __DIR__ . '/../api/financial/entries/delete.php',

    // Fases seguintes adicionarão aqui:
    // '* /sales'  '* /products' ... etc.
];

$key = "{$method} {$uri}";

if (isset($routes[$key])) {
    require $routes[$key];
    exit;
}

Response::error('Rota não encontrada.', 404);
