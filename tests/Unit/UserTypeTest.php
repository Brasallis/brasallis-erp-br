<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Enum\UserType;
use PHPUnit\Framework\TestCase;

class UserTypeTest extends TestCase
{
    public function test_enum_values()
    {
        $this->assertEquals('admin', UserType::ADMIN->value);
        $this->assertEquals('super_admin', UserType::SUPER_ADMIN->value);
        $this->assertEquals('employee', UserType::EMPLOYEE->value);
    }

    public function test_enum_labels()
    {
        $this->assertEquals('Administrador de Unidade', UserType::ADMIN->label());
        $this->assertEquals('Super Administrador', UserType::SUPER_ADMIN->label());
        $this->assertEquals('Colaborador / Operador', UserType::EMPLOYEE->label());
    }
}
