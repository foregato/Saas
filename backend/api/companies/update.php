<?php

use App\Helpers\Request;
use App\Helpers\Response;
use App\Middleware\AuthMiddleware;
use App\Services\CompanyService;

$context = AuthMiddleware::authenticate();

$result = CompanyService::saveOnboarding($context['tenant_id'], Request::body());

if (isset($result['error'])) {
    Response::error($result['error'], 422);
}

Response::success(['company' => $result['company']]);
