<?php

namespace App\Enums;

/**
 * Situação da indicação na triagem da equipe.
 * Nova → Em contato → Agendada → Compareceu / Não compareceu; Descartada a qualquer momento.
 */
enum LeadStatus: string
{
    case New = 'new';
    case Contacting = 'contacting';
    case Scheduled = 'scheduled';
    case Attended = 'attended';
    case NoShow = 'no_show';
    case Discarded = 'discarded';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nova',
            self::Contacting => 'Em contato',
            self::Scheduled => 'Agendada',
            self::Attended => 'Compareceu',
            self::NoShow => 'Não compareceu',
            self::Discarded => 'Descartada',
        };
    }

    /**
     * Situações que dependem de um dia de exame marcado.
     */
    public function needsAppointment(): bool
    {
        return in_array($this, [self::Scheduled, self::Attended, self::NoShow], true);
    }
}
