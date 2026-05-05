<?php
require_once __DIR__ . '/includes/manutencao_guard.php';
require_once __DIR__ . '/bootstrap.php';
check_master_key();

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    
    // Nomes de empresas considerados fictícios
    $fictitious_names = ['Minha Empresa', 'Empresa de Teste', 'Exemplo', 'Demo', 'Teste 123'];
    
    echo "INICIANDO LIMPEZA DE DADOS FICTÍCIOS...\n\n";

    foreach ($fictitious_names as $name) {
        $name_param = "%$name%";
        
        // 1. Identificar IDs das empresas
        $stmt = $pdo->prepare("SELECT id FROM empresas WHERE name LIKE ?");
        $stmt->execute([$name_param]);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            
            echo "Removendo dados vinculados às empresas: " . implode(', ', $ids) . " ($name)\n";
            
            // 2. Remover de tabelas relacionadas (A ordem importa se houver FKs sem CASCADE)
            // Aqui listamos as principais tabelas do sistema
            $tables = ['produtos', 'categorias', 'movimentacoes', 'fiscal_notas', 'usuarios', 'setores', 'chamados_suporte', 'empresas'];
            
            foreach ($tables as $table) {
                try {
                    $column = ($table === 'empresas') ? 'id' : 'empresa_id';
                    $del_stmt = $pdo->prepare("DELETE FROM $table WHERE $column IN ($placeholders)");
                    $del_stmt->execute($ids);
                    echo "- Removidos de $table\n";
                } catch (Exception $e) {
                    echo "- Erro ao remover de $table: " . $e->getMessage() . "\n";
                }
            }
        }
    }

    echo "\nLIMPEZA CONCLUÍDA!";
} catch (Exception $e) {
    echo "ERRO GERAL: " . $e->getMessage();
}

