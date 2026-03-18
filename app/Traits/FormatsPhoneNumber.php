<?php

namespace App\Traits;

trait FormatsPhoneNumber
{
    /**
     * Formats a phone number to the international standard (55 + DDD + Number)
     * and validates the length.
     *
     * @param string $number
     * @return string|null Returns the formatted number or null if invalid.
     */
    protected function formatBrazilianNumber(string $number): ?string
    {
        $digits = preg_replace('/\D/', '', $number);

        // Se já começa com 55
        if (str_starts_with($digits, '55')) {
            // Se tem 12 ou 13 dígitos totais (55 + DDD + 8 ou 9 num)
            if (strlen($digits) >= 12 && strlen($digits) <= 13) {
                return $digits;
            }
        } else {
            // Se não começa com 55, deve ter entre 10 e 11 dígitos (DDD + 8 ou 9 num)
            if (strlen($digits) >= 10 && strlen($digits) <= 11) {
                return '55' . $digits;
            }
        }

        return null;
    }
}
