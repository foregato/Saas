<?php

use App\Auth\AuthService;
use App\Helpers\Request;
use App\Helpers\Response;
use App\Middleware\AuthMiddleware;

AuthMiddleware::authenticate();

$token = Request::bearerToken();
AuthService::logout($token);

Response::success(['message' => 'Sessão encerrada.']);
