<?php

declare(strict_types=1);

namespace Bench\Support;

final class CpfValidator
{
    /**
     * Valida um CPF brasileiro (11 dígitos + dígitos verificadores).
     */
    public static function isValid(mixed $cpf): bool
    {
        if (!is_string($cpf) && !is_int($cpf)) {
            return false;
        }

        $cpf = preg_replace('/\D/', '', (string) $cpf);

        if ($cpf === null || strlen($cpf) !== 11) {
            return false;
        }

        // Rejeita sequências de dígitos iguais (00000000000, 11111111111, ...).
        if (preg_match('/^(\d)\1{10}$/', $cpf) === 1) {
            return false;
        }

        for ($position = 9; $position < 11; $position++) {
            $sum = 0;
            for ($i = 0; $i < $position; $i++) {
                $sum += ((int) $cpf[$i]) * (($position + 1) - $i);
            }

            $digit = ((10 * $sum) % 11) % 10;

            if ((int) $cpf[$position] !== $digit) {
                return false;
            }
        }

        return true;
    }
}
