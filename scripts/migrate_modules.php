<?php
// scripts/migrate_modules.php
require_once __DIR__ . '/../includes/funcoes.php';
require_once __DIR__ . '/../includes/planos_config.php';

$conn = connect_db();
if (!$conn) {
    die("Falha na conexão com o banco de dados.\n");
}

echo "Iniciando migração de módulos para empresas legadas...\n";

try {
    $stmt = $conn->prepare("SELECT id, name, ai_plan, active_modules FROM empresas");
    $stmt->execute();
    $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $migrated = 0;
    foreach ($empresas as $empresa) {
        $id = $empresa['id'];
        $plan = $empresa['ai_plan'] ?? 'foundation';
        $current_modules = json_decode($empresa['active_modules'] ?? '', true);

        if (empty($current_modules)) {
            $default_modules = get_modules_by_plan($plan);
            $json_modules = json_encode($default_modules);
            
            $update = $conn->prepare("UPDATE empresas SET active_modules = ? WHERE id = ?");
            if ($update->execute([$json_modules, $id])) {
                echo "[ID {$id}] Empresa '{$empresa['name']}' migrada para módulos padrão do plano '{$plan}'.\n";
                $migrated++;
            }
        }
    }

    echo "\nMigração concluída! {$migrated} empresas foram atualizadas.\n";

} catch (Exception $e) {
    echo "Erro durante a migração: " . $e->getMessage() . "\n";
}
