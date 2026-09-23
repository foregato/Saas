<?php

use App\Auth\AuthService;
use App\Helpers\Request;
use App\Helpers\Response;

$nome = trim((string) Request::input('nome', ''));
$email = trim(strtolower((string) Request::input('email', '')));
$telefone = trim((string) Request::input('telefone', ''));
$password = (string) Request::input('senha', '');

if ($nome === '' || $email === '' || $password === '') {
    Response::error('Nome, e-mail e senha são obrigatórios.', 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    Response::error('E-mail inválido.', 422);
}
if (strlen($password) < 8) {
    Response::error('A senha deve ter no mínimo 8 caracteres.', 422);
}

$result = AuthService::register($nome, $email, $telefone, $password);

if (isset($result['error'])) {
    Response::error($result['error'], 422);
}

Response::success($result, 201);
