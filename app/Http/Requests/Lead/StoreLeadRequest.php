<?php

namespace App\Http\Requests\Lead;

use App\Http\Requests\Auth\NormalizesPhoneNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
{
    use NormalizesPhoneNumber;

    /**
     * Só quem tem cadastro de prospector pode indicar.
     */
    public function authorize(): bool
    {
        return $this->user()?->prospector !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'digits_between:10,11'],
            'city_id' => ['required', 'integer', Rule::exists('cities', 'id')->where('active', true)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone_number.digits_between' => 'Informe o telefone com DDD.',
        ];
    }
}
