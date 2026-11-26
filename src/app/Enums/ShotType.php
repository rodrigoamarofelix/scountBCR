<?php

namespace App\Enums;

enum ShotType: string
{
    case TwoPoint = '2PT';
    case ThreePoint = '3PT';
    case FreeThrow = 'FT';

    public function label(): string
    {
        return match ($this) {
            self::TwoPoint => '2 pontos',
            self::ThreePoint => '3 pontos',
            self::FreeThrow => 'Lance livre',
        };
    }

    public function pointsValue(): int
    {
        return match ($this) {
            self::TwoPoint => 2,
            self::ThreePoint => 3,
            self::FreeThrow => 1,
        };
    }
}
