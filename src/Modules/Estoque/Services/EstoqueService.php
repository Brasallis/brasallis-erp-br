<?php

namespace App\Modules\Estoque\Services;

use App\Modules\Estoque\Repositories\ProdutoRepository;
use App\Modules\Estoque\Repositories\CategoriaRepository;
use PDOException;

/**
 * EstoqueService — lógica de negócio do módulo de estoque.
 */
class EstoqueService
{
    public function __construct(
        private ProdutoRepository  $produtos,
        private CategoriaRepository $categorias
    ) {}

    /**
     * Cria um produto aplicando validações de negócio.
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function criarProduto(array $data, int $userId): int
    {
        $this->validarProduto($data);

        try {
            return $this->produtos->add($data, $userId);
        } catch (PDOException $e) {
            throw $this->traduzirExcecao($e);
        }
    }

    /**
     * Atualiza um produto existente.
     */
    public function atualizarProduto(array $data): void
    {
        $this->validarProduto($data);

        try {
            $this->produtos->update($data);
        } catch (PDOException $e) {
            throw $this->traduzirExcecao($e);
        }
    }

    /**
     * Remove um produto.
     */
    public function removerProduto(int $id): void
    {
        try {
            $this->produtos->delete($id);
        } catch (PDOException $e) {
            throw $this->traduzirExcecao($e);
        }
    }

    /**
     * Retorna lista paginada de produtos.
     */
    public function listarProdutos(array $filtros = []): array
    {
        $search      = $filtros['search']      ?? '';
        $categoriaId = $filtros['categoria_id'] ?? 'all';
        $page        = max(1, (int)($filtros['page'] ?? 1));
        $limit       = 10;
        $offset      = ($page - 1) * $limit;
        $lowStock    = ($filtros['filter'] ?? '') === 'low_stock';

        $total = $this->produtos->countAll($search, $categoriaId, $lowStock);
        $items = $this->produtos->getAll($search, $categoriaId, $limit, $offset, $lowStock);

        return [
            'items'        => $items,
            'total'        => $total,
            'page'         => $page,
            'total_pages'  => $total > 0 ? (int)ceil($total / $limit) : 1,
            'search'       => $search,
            'categoria_id' => $categoriaId,
            'low_stock'    => $lowStock,
        ];
    }

    public function buscarProduto(int $id): ?array
    {
        return $this->produtos->findById($id);
    }

    public function listarCategorias(string $search = ''): array
    {
        return $this->categorias->getAll($search);
    }

    public function getCategories(): array
    {
        return $this->produtos->getCategories();
    }

    public function criarCategoria(string $nome): int
    {
        $nome = trim($nome);
        if (empty($nome)) {
            throw new \InvalidArgumentException('O nome da categoria não pode ser vazio.');
        }
        return $this->categorias->add($nome);
    }

    public function atualizarCategoria(int $id, string $nome): void
    {
        $nome = trim($nome);
        if (empty($nome)) {
            throw new \InvalidArgumentException('O nome da categoria não pode ser vazio.');
        }
        $this->categorias->update($id, $nome);
    }

    public function removerCategoria(int $id): void
    {
        $this->categorias->delete($id);
    }

    public function buscarCategoria(int $id): ?array
    {
        return $this->categorias->findById($id);
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    private function validarProduto(array $data): void
    {
        if (empty($data['name'])) {
            throw new \InvalidArgumentException('Nome do produto é obrigatório.');
        }
        if (!isset($data['price']) || $data['price'] < 0) {
            throw new \InvalidArgumentException('Preço de venda inválido.');
        }
        if (!isset($data['cost_price']) || $data['cost_price'] < 0) {
            throw new \InvalidArgumentException('Preço de custo inválido.');
        }
    }

    private function traduzirExcecao(PDOException $e): \RuntimeException
    {
        $code = $e->errorInfo[1] ?? 0;
        $msg  = match ($code) {
            1062 => 'O SKU informado já existe para outro produto.',
            1451 => 'Não é possível excluir este produto pois ele possui movimentações associadas.',
            default => 'Erro no banco de dados: ' . $e->getMessage(),
        };
        return new \RuntimeException($msg, $code, $e);
    }
}
