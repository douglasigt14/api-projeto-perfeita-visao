<?php

namespace App\Http\Requests\Admin;

use App\Enums\LeadStage;
use App\Enums\LeadStatusColor;
use App\Models\LeadStatus;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Cadastro e edição de situação. A etapa não muda depois de criada. A situação padrão da etapa
 * não pode ser desativada nem deixar de ser padrão direto — marca-se outra como padrão.
 */
class LeadStatusRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $creating = $this->isMethod('post');
        $required = $creating ? 'required' : 'sometimes';
        /** @var LeadStatus|null $status */
        $status = $this->route('leadStatus');

        return [
            'name' => [$required, 'string', 'max:60'],
            'partner_name' => [$required, 'string', 'max:60'],
            'stage' => $creating ? ['required', Rule::enum(LeadStage::class)] : ['prohibited'],
            'color' => [$required, Rule::enum(LeadStatusColor::class)],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:9999'],
            'is_default' => [
                'sometimes',
                'boolean',
                function (string $attribute, mixed $value, Closure $fail) use ($status) {
                    if ($status?->is_default && ! $this->boolean('is_default')) {
                        $fail('Para trocar a padrão, marque outra situação desta etapa como padrão.');
                    }
                    if ($this->boolean('is_default') && ! $this->boolean('active', $status->active ?? true)) {
                        $fail('A situação padrão precisa estar ativa.');
                    }
                },
            ],
            'active' => [
                'sometimes',
                'boolean',
                function (string $attribute, mixed $value, Closure $fail) use ($status) {
                    if ($status?->is_default && ! $this->boolean('active')) {
                        $fail('A situação padrão da etapa não pode ser desativada. Marque outra como padrão antes.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'stage.prohibited' => 'A etapa não muda depois de criada. Crie outra situação na etapa certa.',
        ];
    }
}
