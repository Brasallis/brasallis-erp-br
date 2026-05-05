<?php
// superadmin/excluir_empresa.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/funcoes.php';
checkSuperAdmin();

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn = connect_db();

    try {
        $conn->beginTransaction();

        // 1. Remove os usuários da empresa (ou poderíamos apenas desativar)
        $stmt_users = $conn->prepare("DELETE FROM usuarios WHERE empresa_id = ?");
        $stmt_users->execute([$id]);

        // 2. Remove a empresa
        $stmt = $conn->prepare("DELETE FROM empresas WHERE id = ?");
        $stmt->execute([$id]);

        $conn->commit();
        $_SESSION['msg_success'] = "Empresa removida com sucesso!";
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['msg_error'] = "Erro ao excluir: " . $e->getMessage();
    }
}

header("Location: empresas.php");
exit;
