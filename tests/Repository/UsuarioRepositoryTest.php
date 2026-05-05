<?php
declare(strict_types=1);

namespace Tests\Repository;

use App\Repository\UsuarioRepository;
use PHPUnit\Framework\TestCase;
use PDO;
use PDOStatement;

class UsuarioRepositoryTest extends TestCase
{
    private $pdo;
    private $stmt;
    private $repository;
    private $empresaId = 1;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(PDO::class);
        $this->stmt = $this->createMock(PDOStatement::class);
        $this->repository = new UsuarioRepository($this->pdo, $this->empresaId);
    }

    public function test_get_all_executes_correct_sql()
    {
        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT * FROM usuarios WHERE empresa_id = :empresa_id'))
            ->willReturn($this->stmt);

        $this->stmt->expects($this->exactly(1))
            ->method('bindValue')
            ->with(':empresa_id', $this->empresaId);

        $this->stmt->expects($this->once())
            ->method('execute');

        $this->stmt->expects($this->once())
            ->method('fetchAll')
            ->willReturn([['id' => 1, 'username' => 'testuser']]);

        $result = $this->repository->getAll();
        
        $this->assertCount(1, $result);
        $this->assertEquals('testuser', $result[0]['username']);
    }

    public function test_get_by_id_returns_user()
    {
        $userId = 123;
        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('id = ? AND empresa_id = ?'))
            ->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([$userId, $this->empresaId]);

        $this->stmt->expects($this->once())
            ->method('fetch')
            ->willReturn(['id' => $userId, 'username' => 'john_doe']);

        $result = $this->repository->getById($userId);
        
        $this->assertEquals('john_doe', $result['username']);
    }
}
