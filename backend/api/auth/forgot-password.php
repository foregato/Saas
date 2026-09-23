<?php

use App\Auth\PasswordResetService;
use App\Helpers\Request;
use App\Helpers\Response;

$email = trim(strtolower((string) Request::input('email', '')));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    Response::error('E-mail inválido.', 422);
}

PasswordResetService::requestReset($email);

Response::success(['message' => 'Se o e-mail existir em nossa base, um link de recuperação foi enviado.']);
