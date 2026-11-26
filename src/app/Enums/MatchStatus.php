<?php

namespace App\Enums;

enum MatchStatus: string
{
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Finished = 'finished';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Agendada',
            self::InProgress => 'Em andamento',
            self::Finished => 'Finalizada',
        };
    }
}
