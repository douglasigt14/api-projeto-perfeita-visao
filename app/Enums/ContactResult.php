<?php

namespace App\Enums;

enum ContactResult: string
{
    case Reached = 'reached';
    case NoAnswer = 'no_answer';
    case WrongNumber = 'wrong_number';
    case NotInterested = 'not_interested';

    public function label(): string
    {
        return match ($this) {
            self::Reached => 'Falou com a pessoa',
            self::NoAnswer => 'Não atendeu',
            self::WrongNumber => 'Número errado',
            self::NotInterested => 'Não tem interesse',
        };
    }
}
