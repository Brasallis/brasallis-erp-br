<?php
namespace App\Services;

class FiscalIntegrator {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Gera nota fiscal de SAÍDA a partir de uma venda (PDV)
     */
    public function createFromSale($vendaId, $empresaId, $userId) {
        // 1. Buscar dados da venda
        $stmt = $this->conn->prepare("SELECT * FROM vendas WHERE id = ? AND empresa_id = ?");
        $stmt->execute([$vendaId, $empresaId]);
        $venda = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$venda) return false;

        // 2. Determinar número da nota (sequencial simples por enquanto)
        $numStmt = $this->conn->prepare("SELECT MAX(CAST(numero AS UNSIGNED)) FROM fiscal_notas WHERE empresa_id = ? AND tipo = 'saida'");
        $numStmt->execute([$empresaId]);
        $nextNum = ($numStmt->fetchColumn() ?: 0) + 1;

        // 3. Preparar dados
        // Nota: Em PDV, o cliente muitas vezes é "Consumidor Final" não identificado
        $destinatario = 'CONSUMIDOR FINAL';
        $cpfCnpj = ''; // Poderia vir da venda se tivesse cliente vinculado
        
        // Simulação de Impostos (Simples Nacional aprox. 4% a 10% dependendo da faixa)
        // Para MVP, vamos zerar ou usar um fixo simples, pois PDV real precisaria de NCM de cada item
        $valorTotal = $venda['total_amount'];
        $valorImpostos = $valorTotal * 0.18; // Exemplo genérico 18% carga tributária média

        // 4. Inserir Nota
        $sql = "INSERT INTO fiscal_notas 
                (empresa_id, numero, serie, tipo, modelo, chave_acesso, emitente_destinatario, cpf_cnpj, data_emissao, valor_total, valor_impostos, status, icms_base, icms_valor, ipi_valor, pis_valor, cofins_valor) 
                VALUES 
                (?, ?, ?, 'saida', 'cupom', ?, ?, ?, NOW(), ?, ?, 'autorizada', ?, ?, ?, ?, ?)";
        
        // Gerar chave de acesso fake (44 digitos)
        $chave = $this->generateAccessKey($empresaId, $nextNum);

        $stmtInsert = $this->conn->prepare($sql);
        $stmtInsert->execute([
            $empresaId,
            $nextNum,
            '1', // Serie
            $chave,
            $destinatario,
            $cpfCnpj,
            $valorTotal,
            $valorImpostos,
            $valorTotal, // Base ICMS (Simplificado)
            $valorTotal * 0.18, // Valor ICMS (Simplificado)
            0, // IPI
            0, // PIS
            0  // COFINS
        ]);

        return $this->conn->lastInsertId();
    }

    /**
     * Gera nota fiscal de ENTRADA a partir de uma compra
     */
    public function createFromPurchase($compraId, $empresaId, $userId) {
        // 1. Buscar dados da compra
        $stmt = $this->conn->prepare("
            SELECT c.*, f.name as fornecedor_nome, f.cnpj as fornecedor_cnpj 
            FROM compras c 
            LEFT JOIN fornecedores f ON c.supplier_id = f.id 
            WHERE c.id = ? AND c.empresa_id = ?
        ");
        $stmt->execute([$compraId, $empresaId]);
        $compra = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$compra) return false;

        // 2. Tentar usar número da nota original se tiver path, senão sequencial
        $numero = 'KEY-' . $compraId; // Fallback
        // Se tivéssemos extraido o numero real da nota no upload, usariamos aqui.
        
        // 3. Inserir Nota
        // O valor já vem da compra. Impostos não temos detalhado na tabela compra, assumir 0 ou estimativa
        $valorTotal = $compra['total_amount'];
        $valorImpostos = 0; 

        $sql = "INSERT INTO fiscal_notas 
                (empresa_id, numero, serie, tipo, modelo, chave_acesso, emitente_destinatario, cpf_cnpj, data_emissao, valor_total, valor_impostos, status) 
                VALUES 
                (?, ?, ?, 'entrada', 'nfe', ?, ?, ?, ?, ?, ?, 'autorizada')";
        
        // Chave fake
        $chave = $this->generateAccessKey($empresaId, $compraId);

        $stmtInsert = $this->conn->prepare($sql);
        $stmtInsert->execute([
            $empresaId,
            $compraId, // Numero ficticio usando ID da compra
            '1',
            $chave,
            $compra['fornecedor_nome'] ?: 'Fornecedor Desconhecido',
            $compra['fornecedor_cnpj'] ?: '',
            $compra['purchase_date'],
            $valorTotal,
            $valorImpostos
        ]);
        
        return $this->conn->lastInsertId();
    }

    private function generateAccessKey($empresaId, $numero) {
        // Formato 44 digitos numéricos
        return str_pad((string)$empresaId . date('ym') . $numero, 44, '0', STR_PAD_LEFT);
    }
}
