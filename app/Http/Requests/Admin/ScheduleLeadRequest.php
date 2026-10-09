<?php

namespace App\Http\Requests\Admin;

use App\Models\Lead;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Marca (ou desmarca, com null) o dia do exame. O dia tem que estar dentro do período do atendimento da indicação.
 */
class ScheduleLeadRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Lead $lead */
        $lead = $this->route('lead');
        $visit = $lead->visit;

        $rules = ['present', 'nullable', 'date_format:Y-m-d'];

        if ($visit === null) {
            return ['appointment_date' => [...$rules, 'prohibited']];
        }

        $start = $visit->visit_date->toDateString();
        $end = ($visit->end_date ?? $visit->visit_date)->toDateString();

        return ['appointment_date' => [...$rules, "after_or_equal:{$start}", "before_or_equal:{$end}"]];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'appointment_date.prohibited' => 'Esta indicação não tem atendimento; não dá para marcar o exame.',
            'appointment_date.after_or_equal' => 'Escolha um dia dentro do período do atendimento.',
            'appointment_date.before_or_equal' => 'Escolha um dia dentro do período do atendimento.',
        ];
    }
}
