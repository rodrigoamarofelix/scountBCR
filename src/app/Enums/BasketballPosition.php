<?php

namespace App\Enums;

enum BasketballPosition: string
{
    case PointGuard = 'armador';
    case ShootingGuard = 'ala_armador';
    case SmallForward = 'ala';
    case PowerForward = 'ala_pivo';
    case Center = 'pivo';

    public function label(): string
    {
        return match ($this) {
            self::PointGuard => 'Armador',
            self::ShootingGuard => 'Ala-armador',
            self::SmallForward => 'Ala',
            self::PowerForward => 'Ala-pivô',
            self::Center => 'Pivô',
        };
    }
}
