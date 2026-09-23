<?php

use App\Auth\AuthService;
use App\Helpers\Request;
use App\Helpers\Response;

$email = trim(strtolower((string) Request::input('email', '')));
$password = (string) Request::input('senha', '');

if ($email === '' || $password === '') {
    Response::error('E-mail e senha são obrigatórios.', 422);
}

$result = AuthService::login($email, $password);

if (isset($result['error'])) {
    Response::error($result['error'], 401);
}

Response::success($result);
