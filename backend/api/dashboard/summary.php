<?php

use App\Helpers\Request;
use App\Helpers\Response;
use App\Middleware\AuthMiddleware;
use App\Services\DashboardService;

$context = AuthMiddleware::authenticate();

$period = (string) Request::query('period', 'this_month');
$allowed = ['this_month', 'last_month', 'last_3_months', 'this_year', 'custom'];
if (!in_array($period, $allowed, true)) {
    $period = 'this_month';
}

$start = Request::query('start');
$end = Request::query('end');

$summary = DashboardService::summary($context['tenant_id'], $period, $start, $end);

Response::success($summary);
