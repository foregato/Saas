<?php

namespace App\Helpers;

class Period
{
    /**
     * Resolve um período do dashboard a partir da query string.
     * Retorna ['start' => 'Y-m-d', 'end' => 'Y-m-d', 'prev_start' => ..., 'prev_end' => ...]
     */
    public static function resolve(string $period, ?string $customStart, ?string $customEnd): array
    {
        $today = new \DateTime('today');

        switch ($period) {
            case 'last_month':
                $start = (clone $today)->modify('first day of last month');
                $end = (clone $today)->modify('last day of last month');
                break;

            case 'last_3_months':
                $start = (clone $today)->modify('first day of this month')->modify('-2 months');
                $end = (clone $today)->modify('last day of this month');
                break;

            case 'this_year':
                $start = new \DateTime($today->format('Y') . '-01-01');
                $end = new \DateTime($today->format('Y') . '-12-31');
                break;

            case 'custom':
                if (!self::isValidDate($customStart) || !self::isValidDate($customEnd)) {
                    // fallback seguro para o mês atual se as datas forem inválidas
                    $start = (clone $today)->modify('first day of this month');
                    $end = (clone $today)->modify('last day of this month');
                } else {
                    $start = new \DateTime($customStart);
                    $end = new \DateTime($customEnd);
                }
                break;

            case 'this_month':
            default:
                $start = (clone $today)->modify('first day of this month');
                $end = (clone $today)->modify('last day of this month');
                break;
        }

        // Período anterior de mesma duração, imediatamente antes do início atual.
        $days = (int) $start->diff($end)->format('%a');
        $prevEnd = (clone $start)->modify('-1 day');
        $prevStart = (clone $prevEnd)->modify("-{$days} days");

        return [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
            'prev_start' => $prevStart->format('Y-m-d'),
            'prev_end' => $prevEnd->format('Y-m-d'),
        ];
    }

    private static function isValidDate(?string $value): bool
    {
        if (!$value) {
            return false;
        }
        $d = \DateTime::createFromFormat('Y-m-d', $value);
        return $d && $d->format('Y-m-d') === $value;
    }
}
