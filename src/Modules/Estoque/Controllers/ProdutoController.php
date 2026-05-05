<?php

namespace App\Modules\Estoque\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Modules\Estoque\Services\EstoqueService;

/**
 * ProdutoController — gerencia HTTP para o módulo de produtos.
 */
class ProdutoController
{
    public function __construct(private EstoqueService $service) {}

    /** GET /estoque/produtos */
    public function index(Request $request, Response $response): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $dados     = $this->service->listarProdutos($request->all());
        $categorias = $this->service->getCategories();

        // Flash message
        $message      = $_SESSION['message']      ?? null;
        $messageType  = $_SESSION['message_type'] ?? 'info';
        unset($_SESSION['message'], $_SESSION['message_type']);

        $response->view('estoque/produtos/index', array_merge($dados, compact('categorias', 'message', 'messageType')));
    }

    /** POST /estoque/produtos */
    public function store(Request $request, Response $response): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = $_SESSION['user_id'] ?? 0;

        try {
            $this->service->criarProduto($request->all(), $userId);
            $_SESSION['message']      = 'Produto adicionado com sucesso!';
            $_SESSION['message_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['message']      = $e->getMessage();
            $_SESSION['message_type'] = 'danger';
        }

        $response->redirect('/estoque/produtos');
    }

    /** PUT /estoque/produtos/{id} — via _method override */
    public function update(Request $request, Response $response, array $params): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $data     = array_merge($request->all(), ['id' => $params['id']]);
        try {
            $this->service->atualizarProduto($data);
            $_SESSION['message']      = 'Produto atualizado com sucesso!';
            $_SESSION['message_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['message']      = $e->getMessage();
            $_SESSION['message_type'] = 'danger';
        }

        $response->redirect('/estoque/produtos');
    }

    /** DELETE /estoque/produtos/{id} — via _method override */
    public function destroy(Request $request, Response $response, array $params): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $this->service->removerProduto((int)$params['id']);
            $_SESSION['message']      = 'Produto excluído com sucesso!';
            $_SESSION['message_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['message']      = $e->getMessage();
            $_SESSION['message_type'] = 'danger';
        }

        $response->redirect('/estoque/produtos');
    }

    /** GET /api/v1/estoque/produtos/{id} — JSON para AJAX */
    public function show(Request $request, Response $response, array $params): void
    {
        $produto = $this->service->buscarProduto((int)$params['id']);
        if (!$produto) {
            $response->json(['error' => 'Produto não encontrado.'], 404);
        }
        $response->json($produto);
    }

    /** GET /api/v1/estoque/produtos/search?q= — autocomplete */
    public function search(Request $request, Response $response): void
    {
        // Para busca JSON rápida (PDV, etc.)
        $response->redirect('/estoque/produtos');
    }
}
