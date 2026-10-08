<?php

namespace App\Http\Requests\Auth;

trait NormalizesPhoneNumber
{
    /**
     * Guarda o telefone só com dígitos: "(88) 99999-1234" -> "88999991234".
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->phone_number)) {
            $this->merge(['phone_number' => preg_replace('/\D/', '', $this->phone_number)]);
        }
    }
}
