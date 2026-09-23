<?php

use App\Helpers\Request;
use App\Helpers\Response;
use App\Middleware\AuthMiddleware;
use App\Services\FinancialCategoryService;

$context = AuthMiddleware::authenticate();

$id = (int) Request::query('id', 0);
if ($id <= 0) {
    Response::error('Informe o id da categoria (?id=).', 422);
}

$result = FinancialCategoryService::update($context['tenant_id'], $id, Request::body());

if (isset($result['error'])) {
    Response::error($result['error'], 422);
}

Response::success(['category' => $result['category']]);
