<?php

namespace App\Modules\Dte\Domain\Enums;

enum PrintFormat: string
{
    case LETTER = 'letter';
    case TICKET_80 = 'ticket_80';
    case TICKET_58 = 'ticket_58';

    public function isTicket(): bool
    {
        return match ($this) {
            self::LETTER => false,
            self::TICKET_80,
            self::TICKET_58 => true,
        };
    }
}
