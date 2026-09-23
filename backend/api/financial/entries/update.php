<?php

use App\Helpers\Request;
use App\Helpers\Response;
use App\Middleware\AuthMiddleware;
use App\Services\FinancialEntryService;

$context = AuthMiddleware::authenticate();

$id = (int) Request::query('id', 0);
if ($id <= 0) {
    Response::error('Informe o id do lançamento (?id=).', 422);
}

$result = FinancialEntryService::update($context['tenant_id'], $id, Request::body());

if (isset($result['error'])) {
    Response::error($result['error'], 422);
}

Response::success(['entry' => $result['entry']]);
