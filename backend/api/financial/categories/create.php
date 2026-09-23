<?php

use App\Helpers\Request;
use App\Helpers\Response;
use App\Middleware\AuthMiddleware;
use App\Services\FinancialCategoryService;

$context = AuthMiddleware::authenticate();

$result = FinancialCategoryService::create($context['tenant_id'], Request::body());

if (isset($result['error'])) {
    Response::error($result['error'], 422);
}

Response::success(['category' => $result['category']], 201);
