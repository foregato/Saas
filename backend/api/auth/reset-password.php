<?php

use App\Auth\PasswordResetService;
use App\Helpers\Request;
use App\Helpers\Response;

$token = (string) Request::input('token', '');
$newPassword = (string) Request::input('nova_senha', '');

if ($token === '' || strlen($newPassword) < 8) {
    Response::error('Token inválido ou senha muito curta (mínimo 8 caracteres).', 422);
}

$ok = PasswordResetService::reset($token, $newPassword);

if (!$ok) {
    Response::error('Token inválido ou expirado. Solicite uma nova recuperação de senha.', 422);
}

Response::success(['message' => 'Senha alterada com sucesso.']);
