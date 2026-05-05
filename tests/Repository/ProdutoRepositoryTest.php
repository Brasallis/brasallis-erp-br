<?php
declare(strict_types=1);

namespace Tests\Repository;

use App\Repository\ProdutoRepository;
use PHPUnit\Framework\TestCase;
use PDO;
use PDOStatement;

class ProdutoRepositoryTest extends TestCase
{
    private $pdo;
    private $stmt;
    private $repository;
    private $empresaId = 1;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(PDO::class);
        $this->stmt = $this->createMock(PDOStatement::class);
        $this->repository = new ProdutoRepository($this->pdo, $this->empresaId);
    }

    public function test_find_by_id_returns_product()
    {
        $productId = 1;
        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT * FROM produtos WHERE id = ?'))
            ->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([$productId, $this->empresaId]);

        $this->stmt->expects($this->once())
            ->method('fetch')
            ->willReturn(['id' => 1, 'name' => 'Produto Teste']);

        $result = $this->repository->findById($productId);
        
        $this->assertEquals('Produto Teste', $result['name']);
    }

    public function test_delete_executes_delete_query()
    {
        $productId = 1;
        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('DELETE FROM produtos WHERE id = ?'))
            ->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([$productId, $this->empresaId])
            ->willReturn(true);

        $result = $this->repository->delete($productId);
        $this->assertTrue($result);
    }
}
