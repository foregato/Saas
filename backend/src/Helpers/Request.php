<?php

namespace App\Helpers;

class Request
{
    private static ?array $body = null;

    public static function body(): array
    {
        if (self::$body !== null) {
            return self::$body;
        }

        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw, true);
        self::$body = is_array($decoded) ? $decoded : [];
        return self::$body;
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        $body = self::body();
        return $body[$key] ?? $default;
    }

    public static function bearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if ($header === '' && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $header = $headers['Authorization'] ?? '';
        }
        if (preg_match('/Bearer\s+(\S+)/i', $header, $m)) {
            return $m[1];
        }
        return null;
    }

    public static function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public static function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }
}
