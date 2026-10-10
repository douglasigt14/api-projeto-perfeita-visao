<?php

namespace App\Enums;

/**
 * Etapa da indicação na triagem da equipe. Fixa no código: as regras do sistema dependem dela.
 * As situações (tabela lead_statuses) são criadas pela equipe, cada uma dentro de uma etapa.
 * Nova → Em contato → Agendada → Compareceu / Não compareceu; Descartada a qualquer momento.
 */
enum LeadStage: string
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
     * Etapas que dependem de um dia de exame marcado.
     */
    public function needsAppointment(): bool
    {
        return in_array($this, [self::Scheduled, self::Attended, self::NoShow], true);
    }
}
