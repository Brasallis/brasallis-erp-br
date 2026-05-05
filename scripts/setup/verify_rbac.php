<?php
require_once __DIR__ . '/../../includes/db_config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Conectado ao banco.\n";

    // 1. Check Modulos Table
    $stmt = $pdo->query("SHOW TABLES LIKE 'modulos'");
    if ($stmt->rowCount() == 0) {
        echo "Tabelas RBAC não encontradas. Criando...\n";
        
        $sql = file_get_contents(__DIR__ . '/../migrations/001_create_pyramid_architecture.sql');
        $pdo->exec($sql);
        echo "Migração executada com sucesso.\n";
    } else {
        echo "Tabela 'modulos' já existe.\n";
    }

    // 2. Check Setores Table
    $stmt = $pdo->query("SHOW TABLES LIKE 'setores'");
    if ($stmt->rowCount() > 0) {
        echo "Tabela 'setores' OK.\n";
    }

    // 3. Seed Default 'Administrador' Sector for existing companies
    // Find companies without sectors
    $companies = $pdo->query("SELECT id, nome FROM empresas")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($companies as $company) {
        $stmt = $pdo->prepare("SELECT id FROM setores WHERE empresa_id = ? AND nome = 'Administração'");
        $stmt->execute([$company['id']]);
        if ($stmt->rowCount() == 0) {
            echo "Criando setor 'Administração' para empresa ID {$company['id']}...\n";
            $pdo->prepare("INSERT INTO setores (empresa_id, nome) VALUES (?, 'Administração')")->execute([$company['id']]);
            $sectorId = $pdo->lastInsertId();
            
            // Give Admin full access to all modules
            $modules = $pdo->query("SELECT id FROM modulos")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($modules as $mod) {
                $pdo->prepare("INSERT INTO permissoes_setor (setor_id, modulo_id, nivel_acesso) VALUES (?, ?, 'admin')")
                    ->execute([$sectorId, $mod['id']]);
            }
        }
    }
    
    echo "Verificação concluída.\n";

} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}
