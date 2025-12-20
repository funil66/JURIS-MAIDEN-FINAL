<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CpfCnpj implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $document = preg_replace('/[^0-9]/', '', $value);
        
        if (strlen($document) === 11) {
            if (!$this->validateCpf($document)) {
                $fail('O CPF informado é inválido.');
            }
        } elseif (strlen($document) === 14) {
            if (!$this->validateCnpj($document)) {
                $fail('O CNPJ informado é inválido.');
            }
        } else {
            $fail('O documento deve ser um CPF (11 dígitos) ou CNPJ (14 dígitos).');
        }
    }

    /**
     * Valida CPF brasileiro
     */
    private function validateCpf(string $cpf): bool
    {
        // Verifica se todos os dígitos são iguais
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        // Calcula o primeiro dígito verificador
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += intval($cpf[$i]) * (10 - $i);
        }
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : (11 - $remainder);

        if (intval($cpf[9]) !== $digit1) {
            return false;
        }

        // Calcula o segundo dígito verificador
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += intval($cpf[$i]) * (11 - $i);
        }
        $remainder = $sum % 11;
        $digit2 = ($remainder < 2) ? 0 : (11 - $remainder);

        return intval($cpf[10]) === $digit2;
    }

    /**
     * Valida CNPJ brasileiro
     */
    private function validateCnpj(string $cnpj): bool
    {
        // Verifica se todos os dígitos são iguais
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        // Calcula o primeiro dígito verificador
        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += intval($cnpj[$i]) * $weights1[$i];
        }
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : (11 - $remainder);

        if (intval($cnpj[12]) !== $digit1) {
            return false;
        }

        // Calcula o segundo dígito verificador
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += intval($cnpj[$i]) * $weights2[$i];
        }
        $remainder = $sum % 11;
        $digit2 = ($remainder < 2) ? 0 : (11 - $remainder);

        return intval($cnpj[13]) === $digit2;
    }
}
