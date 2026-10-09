<?php

namespace App\Enums;

enum ContactChannel: string
{
    case WhatsApp = 'whatsapp';
    case PhoneCall = 'phone_call';
    case InPerson = 'in_person';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::WhatsApp => 'WhatsApp',
            self::PhoneCall => 'Ligação',
            self::InPerson => 'Presencial',
            self::Other => 'Outro',
        };
    }
}
