<?php

namespace App\Modules\Dte\Domain\Enums;

enum PrintCopyType: string
{
    case ORIGINAL = 'original';
    case CLIENT = 'client';
    case CEDIBLE = 'cedible';

    public function label(): string
    {
        return match ($this) {
            self::ORIGINAL => 'Original',
            self::CLIENT => 'Copia Cliente',
            self::CEDIBLE => 'Cedible',
        };
    }
}
