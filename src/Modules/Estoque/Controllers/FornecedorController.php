<?php

namespace App\Modules\Estoque\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Modules\Estoque\Repositories\FornecedorRepository;

/**
 * FornecedorController — gerencia as requisições de fornecedores.
 */
class FornecedorController
{
    public function __construct(private FornecedorRepository $repository) {}

    public function index(Request $request, Response $response): void
    {
        $fornecedores = $this->repository->all();
        $response->view('estoque/fornecedores/index', compact('fornecedores'));
    }

    public function store(Request $request, Response $response): void
    {
        $data = $request->all();
        if ($this->repository->create($data)) {
            $response->redirect('/estoque/fornecedores', 'Fornecedor cadastrado com sucesso!', 'success');
        } else {
            $response->redirect('/estoque/fornecedores', 'Erro ao cadastrar fornecedor.', 'danger');
        }
    }

    public function update(Request $request, Response $response, array $params): void
    {
        $id = (int)$params['id'];
        $data = $request->all();
        if ($this->repository->update($id, $data)) {
            $response->redirect('/estoque/fornecedores', 'Fornecedor atualizado com sucesso!', 'success');
        } else {
            $response->redirect('/estoque/fornecedores', 'Erro ao atualizar fornecedor.', 'danger');
        }
    }

    public function destroy(Request $request, Response $response, array $params): void
    {
        $id = (int)$params['id'];
        if ($this->repository->delete($id)) {
            $response->redirect('/estoque/fornecedores', 'Fornecedor removido com sucesso!', 'success');
        } else {
            $response->redirect('/estoque/fornecedores', 'Erro ao remover fornecedor.', 'danger');
        }
    }

    public function show(Request $request, Response $response, array $params): void
    {
        $id = (int)$params['id'];
        $fornecedor = $this->repository->findById($id);
        
        if ($request->isAjax()) {
            $response->json($fornecedor);
            return;
        }

        $response->view('estoque/fornecedores/show', compact('fornecedor'));
    }
}
