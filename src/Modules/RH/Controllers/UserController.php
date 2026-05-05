<?php

namespace App\Modules\RH\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Modules\RH\Services\RhService;
use App\Modules\RH\Repositories\SetorRepository;
use App\Modules\RH\Repositories\CargoRepository;
use App\Modules\Auth\Repositories\UserRepository;
use Exception;

/**
 * UserController — gerencia a equipe (RH) no padrão MVC.
 */
class UserController
{
    public function __construct(
        private RhService $rhService,
        private UserRepository $userRepo,
        private SetorRepository $setorRepo,
        private CargoRepository $cargoRepo
    ) {}

    public function index(Request $request, Response $response): void
    {
        $search = $request->input('search') ?? '';
        // Reaproveitando o UserRepository para listagem
        $usuarios = $this->userRepo->search($search);
        $setores  = $this->setorRepo->all();

        $response->view('rh/usuarios/index', [
            'usuarios' => $usuarios,
            'setores'  => $setores,
            'search'   => $search
        ]);
    }

    public function store(Request $request, Response $response): void
    {
        $data = $request->all();
        
        try {
            $userData = [
                'username'  => $data['username'],
                'email'     => $data['email'],
                'password'  => password_hash($data['password'], PASSWORD_DEFAULT),
                'user_type' => $data['user_type'] ?? 'employee',
                'data_admissao' => !empty($data['data_admissao']) ? $data['data_admissao'] : null,
                'cpf'           => !empty($data['cpf']) ? $data['cpf'] : null,
                'celular'       => !empty($data['celular']) ? $data['celular'] : null,
                'status_colaborador' => $data['status_colaborador'] ?? 'ativo'
            ];

            $setorId = !empty($data['setor_id']) ? (int)$data['setor_id'] : null;
            $cargoInput = $data['cargo_id'] === 'new' 
                ? ['nome' => $data['novo_cargo_nome']] 
                : $data['cargo_id'];

            $this->rhService->cadastrarFuncionario($userData, $setorId, $cargoInput);
            
            $_SESSION['message'] = 'Usuário cadastrado com sucesso!';
            $_SESSION['message_type'] = 'success';
            $response->redirect('/modules/rh/views/colaboradores.php');
        } catch (Exception $e) {
            $_SESSION['message'] = 'Erro ao cadastrar: ' . $e->getMessage();
            $_SESSION['message_type'] = 'danger';
            $response->redirect('/modules/rh/views/colaboradores.php');
        }
    }

    public function update(Request $request, Response $response, array $args): void
    {
        $id = (int)$args['id'];
        $data = $request->all();

        try {
            $userData = [
                'username'  => $data['username'],
                'email'     => $data['email'],
                'user_type' => $data['user_type'],
                'data_admissao' => !empty($data['data_admissao']) ? $data['data_admissao'] : null,
                'cpf'           => !empty($data['cpf']) ? $data['cpf'] : null,
                'celular'       => !empty($data['celular']) ? $data['celular'] : null,
                'status_colaborador' => $data['status_colaborador'] ?? 'ativo'
            ];

            if (!empty($data['password'])) {
                $userData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            $setorId = !empty($data['setor_id']) ? (int)$data['setor_id'] : null;
            $cargoInput = $data['cargo_id'] === 'new' 
                ? ['nome' => $data['novo_cargo_nome']] 
                : $data['cargo_id'];

            $this->rhService->atualizarFuncionario($id, $userData, $setorId, $cargoInput);

            $_SESSION['message'] = 'Usuário atualizado com sucesso!';
            $_SESSION['message_type'] = 'success';
            $response->redirect('/modules/rh/views/colaboradores.php');
        } catch (Exception $e) {
            $_SESSION['message'] = 'Erro ao atualizar: ' . $e->getMessage();
            $_SESSION['message_type'] = 'danger';
            $response->redirect('/modules/rh/views/colaboradores.php');
        }
    }

    public function destroy(Request $request, Response $response, array $args): void
    {
        $id = (int)$args['id'];
        if ($id === (int)$_SESSION['user_id']) {
            $_SESSION['message'] = 'Você não pode se auto-excluir!';
            $_SESSION['message_type'] = 'warning';
            $response->redirect('/modules/rh/views/colaboradores.php');
            return;
        }

        try {
            $this->userRepo->delete($id);
            $_SESSION['message'] = 'Usuário removido com sucesso!';
            $_SESSION['message_type'] = 'success';
            $response->redirect('/modules/rh/views/colaboradores.php');
        } catch (Exception $e) {
            $_SESSION['message'] = 'Erro ao remover: ' . $e->getMessage();
            $_SESSION['message_type'] = 'danger';
            $response->redirect('/modules/rh/views/colaboradores.php');
        }
    }

    /**
     * API: Retorna cargos de um setor (JSON).
     */
    public function getCargos(Request $request, Response $response): void
    {
        $setorId = (int)$request->input('setor_id');
        $cargos = $this->cargoRepo->findBySetor($setorId);
        $response->json($cargos);
    }
}
