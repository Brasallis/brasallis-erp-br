<?php

namespace App\Modules\Admin\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Modules\Admin\Repositories\OrganizacaoRepository;
use Exception;

/**
 * ConfiguracaoController — gerencia definições da empresa e sistema.
 */
class ConfiguracaoController
{
    public function __construct(private OrganizacaoRepository $repo) {}

    public function index(Request $request, Response $response): void
    {
        $empresa = $this->repo->find();
        $stats = $this->repo->getStats();
        $response->view('admin/configuracoes/index', compact('empresa', 'stats'));
    }

    public function update(Request $request, Response $response): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $data = $request->all();
        $empresaId = $_SESSION['empresa_id'] ?? 0;

        // Processar upload do certificado digital
        $file = $request->file('certificado_digital');
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            if ($ext === 'pfx') {
                $uploadDir = BASE_PATH . '/storage/uploads/certificados/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileName = 'cert_' . $empresaId . '_' . time() . '.pfx';
                $destPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    $data['certificado_path'] = '/storage/uploads/certificados/' . $fileName;
                }
            }
        }

        try {
            $this->repo->update($data);
            $_SESSION['success'] = 'Configurações atualizadas com sucesso!';
            $response->redirect('/admin/configuracoes.php');
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erro ao atualizar: ' . $e->getMessage();
            $response->redirect('/admin/configuracoes.php');
        }
    }
}
