<?php

use App\Helpers\Request;
use App\Helpers\Response;
use App\Middleware\AuthMiddleware;
use App\Services\FinancialCategoryService;

$context = AuthMiddleware::authenticate();

$tipo = Request::query('tipo');
$categories = FinancialCategoryService::list($context['tenant_id'], $tipo);

Response::success(['categories' => $categories]);
