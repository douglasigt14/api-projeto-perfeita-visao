<?php

namespace App\Enums;

enum CityVisitStatus: string
{
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Concluído ou cancelado: não recebe mais indicações.
     */
    public function isClosed(): bool
    {
        return $this === self::Completed || $this === self::Cancelled;
    }
}
