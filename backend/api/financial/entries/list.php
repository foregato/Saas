<?php

use App\Helpers\Request;
use App\Helpers\Response;
use App\Middleware\AuthMiddleware;
use App\Services\FinancialEntryService;

$context = AuthMiddleware::authenticate();

$filters = [
    'start' => Request::query('start'),
    'end' => Request::query('end'),
    'tipo' => Request::query('tipo'),
    'category_id' => Request::query('category_id'),
    'status' => Request::query('status'),
    'page' => Request::query('page'),
    'per_page' => Request::query('per_page'),
];

$result = FinancialEntryService::list($context['tenant_id'], $filters);

Response::success($result);
