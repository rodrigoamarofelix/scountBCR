<?php

namespace App\Enums;

enum ShotResult: string
{
    case Made = 'made';
    case Missed = 'missed';

    public function label(): string
    {
        return match ($this) {
            self::Made => 'Convertido',
            self::Missed => 'Não convertido',
        };
    }
}
