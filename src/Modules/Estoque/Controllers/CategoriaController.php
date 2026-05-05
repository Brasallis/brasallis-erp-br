<?php

namespace App\Modules\Estoque\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Modules\Estoque\Services\EstoqueService;

/**
 * CategoriaController — gerencia HTTP para categorias do estoque.
 */
class CategoriaController
{
    public function __construct(private EstoqueService $service) {}

    /** GET /estoque/categorias */
    public function index(Request $request, Response $response): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $search     = $request->query('search', '');
        $categorias = $this->service->listarCategorias($search);

        $message     = $_SESSION['message']      ?? null;
        $messageType = $_SESSION['message_type'] ?? 'info';
        unset($_SESSION['message'], $_SESSION['message_type']);

        $response->view('estoque/categorias/index', compact('categorias', 'search', 'message', 'messageType'));
    }

    /** POST /estoque/categorias */
    public function store(Request $request, Response $response): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $this->service->criarCategoria($request->input('nome', ''));
            $_SESSION['message']      = 'Categoria adicionada com sucesso!';
            $_SESSION['message_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['message']      = $e->getMessage();
            $_SESSION['message_type'] = 'danger';
        }

        $response->redirect('/estoque/categorias');
    }

    /** POST /estoque/categorias/{id}/update (form HTML, sem suporte a PUT nativo) */
    public function update(Request $request, Response $response, array $params): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $this->service->atualizarCategoria((int)$params['id'], $request->input('nome', ''));
            $_SESSION['message']      = 'Categoria atualizada com sucesso!';
            $_SESSION['message_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['message']      = $e->getMessage();
            $_SESSION['message_type'] = 'danger';
        }

        $response->redirect('/estoque/categorias');
    }

    /** POST /estoque/categorias/{id}/delete */
    public function destroy(Request $request, Response $response, array $params): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $this->service->removerCategoria((int)$params['id']);
            $_SESSION['message']      = 'Categoria excluída com sucesso!';
            $_SESSION['message_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['message']      = $e->getMessage();
            $_SESSION['message_type'] = 'danger';
        }

        $response->redirect('/estoque/categorias');
    }

    /** GET /api/v1/estoque/categorias/{id} — JSON para AJAX */
    public function show(Request $request, Response $response, array $params): void
    {
        $cat = $this->service->buscarCategoria((int)$params['id']);
        if (!$cat) {
            $response->json(['error' => 'Categoria não encontrada.'], 404);
        }
        $response->json($cat);
    }
}
