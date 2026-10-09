<?php

namespace App\Enums;

/**
 * Papel de um usuário da equipe interna. Parceiro (prospector) não tem papel: role = null.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case FieldAgent = 'field_agent';
    case Factory = 'factory';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::FieldAgent => 'Atendente de campo',
            self::Factory => 'Operador de fábrica',
        };
    }
}
