<?php
declare(strict_types=1);

namespace App\Enum;

enum ColaboradorStatus: string
{
    case ATIVO = 'ativo';
    case INATIVO = 'inativo';
    case FERIAS = 'ferias';
    case AFASTADO = 'afastado';

    public function label(): string
    {
        return match($this) {
            self::ATIVO => 'Ativo',
            self::INATIVO => 'Inativo',
            self::FERIAS => 'Em Férias',
            self::AFASTADO => 'Afastado / Licença',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::ATIVO => 'success',
            self::INATIVO => 'danger',
            self::FERIAS => 'warning',
            self::AFASTADO => 'secondary',
        };
    }
}
