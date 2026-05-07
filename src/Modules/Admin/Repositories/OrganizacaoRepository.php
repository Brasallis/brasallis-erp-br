<?php

namespace App\Modules\Admin\Repositories;

use PDO;

/**
 * OrganizacaoRepository — gerencia dados da empresa/tenant.
 */
class OrganizacaoRepository
{
    public function __construct(
        private PDO $pdo,
        private int $empresaId
    ) {}

    public function find(): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM empresas WHERE id = ?");
        $stmt->execute([$this->empresaId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getStats(): array
    {
        $stats = [];
        
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE empresa_id = ?");
        $stmt->execute([$this->empresaId]);
        $stats['usuarios'] = $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM produtos WHERE empresa_id = ?");
        $stmt->execute([$this->empresaId]);
        $stats['produtos'] = $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM fornecedores WHERE empresa_id = ?");
        $stmt->execute([$this->empresaId]);
        $stats['fornecedores'] = $stmt->fetchColumn();

        return $stats;
    }

    public function update(array $data): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE empresas SET 
                name = ?, 
                razao_social = ?, 
                cnpj = ?, 
                inscricao_estadual = ?,
                email = ?, 
                phone = ?, 
                address = ?,
                csc_id = ?,
                csc_token = ?,
                ambiente_fiscal = ?,
                certificado_senha = ?,
                certificado_path = ?,
                openai_api_key = ?,
                gemini_api_key = ?,
                mp_access_token = ?, 
                pagarme_key = ?,
                monthly_revenue_goal = ?,
                fixed_costs = ?
             WHERE id = ?"
        );

        // Se não veio um novo path no $data, buscar o atual para não sobrescrever com vazio
        if (!isset($data['certificado_path'])) {
            $current = $this->find();
            $data['certificado_path'] = $current['certificado_path'] ?? null;
        }

        // Criptografar dados sensíveis antes de salvar
        if (!empty($data['certificado_senha'])) {
            $data['certificado_senha'] = \App\Core\Security::encrypt($data['certificado_senha']);
        }
        
        if (!empty($data['openai_api_key'])) {
            $data['openai_api_key'] = \App\Core\Security::encrypt($data['openai_api_key']);
        }
        if (!empty($data['gemini_api_key'])) {
            $data['gemini_api_key'] = \App\Core\Security::encrypt($data['gemini_api_key']);
        }
        if (!empty($data['mp_access_token'])) {
            $data['mp_access_token'] = \App\Core\Security::encrypt($data['mp_access_token']);
        }
        if (!empty($data['pagarme_key'])) {
            $data['pagarme_key'] = \App\Core\Security::encrypt($data['pagarme_key']);
        }

        return $stmt->execute([
            $data['nome_fantasia'] ?? '',
            $data['razao_social'] ?? '',
            $data['cnpj'] ?? '',
            $data['inscricao_estadual'] ?? '',
            $data['email_contato'] ?? '',
            $data['telefone'] ?? '',
            $data['endereco'] ?? '',
            $data['csc_id'] ?? '',
            $data['csc_token'] ?? '',
            $data['ambiente_fiscal'] ?? 'homologacao',
            $data['certificado_senha'] ?? '',
            $data['certificado_path'] ?? null,
            $data['openai_api_key'] ?? null,
            $data['gemini_api_key'] ?? null,
            $data['mp_access_token'] ?? null,
            $data['pagarme_key'] ?? null,
            $data['monthly_revenue_goal'] ?? 0,
            $data['fixed_costs'] ?? 0,
            $this->empresaId
        ]);
    }
}
