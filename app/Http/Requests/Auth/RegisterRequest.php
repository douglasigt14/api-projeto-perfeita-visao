<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    use NormalizesPhoneNumber;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date', 'before:-18 years'],
            'phone_number' => ['required', 'digits_between:10,11', 'unique:users,phone_number', 'unique:prospectors,phone_number'],
            'city_id' => ['required', 'integer', Rule::exists('cities', 'id')->where('active', true)],
            'pix_key' => ['required', 'string', 'max:255'],
            'instagram_handle' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'birth_date.before' => 'É preciso ter 18 anos ou mais.',
            'phone_number.unique' => 'Este telefone já está cadastrado.',
        ];
    }
}
