<?php

namespace App\Helpers;

class Cnpj
{
    public static function digitsOnly(string $cnpj): string
    {
        return preg_replace('/\D/', '', $cnpj) ?? '';
    }

    /** Valida formato (14 dígitos) e dígitos verificadores do CNPJ. */
    public static function isValid(string $cnpj): bool
    {
        $cnpj = self::digitsOnly($cnpj);

        if (strlen($cnpj) !== 14) {
            return false;
        }

        // Rejeita sequências repetidas (ex: 00000000000000), inválidas por definição.
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        return self::checkDigit($cnpj, 12) === (int) $cnpj[12]
            && self::checkDigit($cnpj, 13) === (int) $cnpj[13];
    }

    private static function checkDigit(string $cnpj, int $length): int
    {
        $weights = $length === 12
            ? [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]
            : [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        $sum = 0;
        for ($i = 0; $i < $length; $i++) {
            $sum += (int) $cnpj[$i] * $weights[$i];
        }

        $remainder = $sum % 11;
        return $remainder < 2 ? 0 : 11 - $remainder;
    }

    public static function format(string $cnpj): string
    {
        $cnpj = self::digitsOnly($cnpj);
        if (strlen($cnpj) !== 14) {
            return $cnpj;
        }
        return preg_replace(
            '/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/',
            '$1.$2.$3/$4-$5',
            $cnpj
        ) ?? $cnpj;
    }
}
