<?php

namespace App\Modules\Estoque\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Modules\Estoque\Repositories\CompraRepository;
use App\Modules\Estoque\Repositories\FornecedorRepository;
use App\Modules\Estoque\Repositories\ProdutoRepository;
use App\Modules\Estoque\Services\CompraService;
use Exception;

/**
 * CompraController — gerencia as requisições de compras e entradas.
 */
class CompraController
{
    public function __construct(
        private CompraRepository $repository,
        private FornecedorRepository $fornecedorRepo,
        private ProdutoRepository $produtoRepo,
        private CompraService $service
    ) {}

    public function index(Request $request, Response $response): void
    {
        $compras = $this->repository->all();
        $response->view('estoque/compras/index', compact('compras'));
    }

    public function create(Request $request, Response $response): void
    {
        $fornecedores = $this->fornecedorRepo->all();
        $response->view('estoque/compras/create', compact('fornecedores'));
    }

    public function store(Request $request, Response $response): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $data = [
            'supplier_id' => $request->input('supplier_id'),
            'purchase_date' => $request->input('purchase_date') ?: date('Y-m-d'),
            'user_id' => $_SESSION['user_id'],
            'fiscal_note_path' => null // Lógica de upload pode ser adicionada aqui
        ];

        $items = json_decode($request->input('items_json'), true);

        if (empty($data['supplier_id']) || empty($items)) {
            $response->redirect('/estoque/compras/create', 'Fornecedor e itens são obrigatórios.', 'danger');
            return;
        }

        try {
            $purchaseId = $this->service->registrarCompra($data, $items);
            $response->redirect("/estoque/compras/{$purchaseId}", 'Compra registrada com sucesso!', 'success');
        } catch (Exception $e) {
            $response->redirect('/estoque/compras/create', 'Erro ao registrar compra: ' . $e->getMessage(), 'danger');
        }
    }

    public function show(Request $request, Response $response, array $params): void
    {
        $id = (int)$params['id'];
        $compra = $this->repository->findById($id);

        if (!$compra) {
            $response->abort(404);
            return;
        }

        $response->view('estoque/compras/show', compact('compra'));
    }
}
