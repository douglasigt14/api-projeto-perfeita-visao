<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContactChannel;
use App\Enums\ContactResult;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadContactRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'channel' => ['required', Rule::enum(ContactChannel::class)],
            'result' => ['required', Rule::enum(ContactResult::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'channel.required' => 'Escolha como foi o contato.',
            'result.required' => 'Escolha o resultado do contato.',
        ];
    }
}
