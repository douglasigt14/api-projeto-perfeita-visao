<?php

namespace App\Http\Requests\Admin;

use App\Enums\LeadStatus;
use App\Models\Lead;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Muda a situação da indicação. "Agendada" só pela rota de agendar; "Compareceu"/"Não compareceu" exigem dia marcado.
 */
class UpdateLeadStatusRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::enum(LeadStatus::class)->except([LeadStatus::Scheduled]),
                function (string $attribute, mixed $value, Closure $fail) {
                    /** @var Lead $lead */
                    $lead = $this->route('lead');
                    $status = LeadStatus::tryFrom($value);
                    if ($status?->needsAppointment() && $lead->appointment_date === null) {
                        $fail('Marque o dia do exame antes.');
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
            'status.enum' => 'Para agendar, escolha o dia do exame.',
        ];
    }
}
