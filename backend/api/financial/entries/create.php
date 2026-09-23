<?php

use App\Helpers\Request;
use App\Helpers\Response;
use App\Middleware\AuthMiddleware;
use App\Services\FinancialEntryService;

$context = AuthMiddleware::authenticate();

$result = FinancialEntryService::create($context['tenant_id'], Request::body());

if (isset($result['error'])) {
    Response::error($result['error'], 422);
}

Response::success(['entry' => $result['entry']], 201);
