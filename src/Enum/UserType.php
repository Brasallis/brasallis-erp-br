<?php
declare(strict_types=1);

namespace App\Enum;

enum UserType: string
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';
    case EMPLOYEE = 'employee';

    public function label(): string
    {
        return match($this) {
            self::SUPER_ADMIN => 'Super Administrador',
            self::ADMIN => 'Administrador de Unidade',
            self::EMPLOYEE => 'Colaborador / Operador',
        };
    }
}
