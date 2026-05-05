<?php
declare(strict_types=1);

namespace App\Enum;

enum SubscriptionStatus: string
{
    case ACTIVE = 'active';
    case OVERDUE = 'overdue';
    case BLOCKED = 'blocked';
    case TRIAL = 'trial';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Ativo',
            self::OVERDUE => 'Vencido',
            self::BLOCKED => 'Bloqueado',
            self::TRIAL => 'Período de Teste',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::ACTIVE => 'success',
            self::OVERDUE => 'warning',
            self::BLOCKED => 'danger',
            self::TRIAL => 'info',
        };
    }
}
