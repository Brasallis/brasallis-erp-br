<?php
// modules/fiscal/views/nota_form.php
session_start();
require_once __DIR__ . '/../../../includes/funcoes.php';

// Check Auth & Permission
if (!isset($_SESSION['user_id'])) { header('Location: ../../../login.php'); exit; }
if (!check_permission('fiscal', 'escrita')) { header('Location: ../../../admin/painel_admin.php?error=acesso_negado'); exit; }

$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tipo_get = isset($_GET['tipo']) ? $_GET['tipo'] : 'saida';
$nota = null;

// Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'];
    $numero = trim($_POST['numero']);
    $em_dest = trim($_POST['emitente_destinatario']);
    $data_em = $_POST['data_emissao'];
    $v_total = (float)str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor_total']);
    $v_imp = (float)str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor_impostos']);
    $status = $_POST['status'];
    $chave = trim($_POST['chave_acesso']);

    try {
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE fiscal_notas SET tipo=?, numero=?, emitente_destinatario=?, data_emissao=?, valor_total=?, valor_impostos=?, status=?, chave_acesso=? WHERE id=? AND empresa_id=?");
            $stmt->execute([$tipo, $numero, $em_dest, $data_em, $v_total, $v_imp, $status, $chave, $id, $empresa_id]);
            header("Location: notas.php?msg=updated");
            exit;
        } else {
            $stmt = $conn->prepare("INSERT INTO fiscal_notas (empresa_id, tipo, numero, emitente_destinatario, data_emissao, valor_total, valor_impostos, status, chave_acesso) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$empresa_id, $tipo, $numero, $em_dest, $data_em, $v_total, $v_imp, $status, $chave]);
            header("Location: notas.php?msg=created");
            exit;
        }
    } catch (Exception $e) { $error = "Erro ao salvar a nota: " . $e->getMessage(); }
}

// Fetch Existing
if ($id > 0) {
    try {
        $stmt = $conn->prepare("SELECT * FROM fiscal_notas WHERE id = ? AND empresa_id = ?");
        $stmt->execute([$id, $empresa_id]);
        $nota = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$nota) die("Nota fiscal não encontrada.");
        $tipo_get = $nota['tipo'];
    } catch (Exception $e) { die("Erro ao buscar nota."); }
}

require_once __DIR__ . '/../../../includes/cabecalho.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-navy mb-1"><i class="fas fa-file-invoice me-2 text-primary"></i><?= $id > 0 ? 'Editar' : 'Lançar' ?> NFe / NFCe</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="../../../admin/painel_admin.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Fiscal</a></li>
                    <li class="breadcrumb-item"><a href="notas.php">Notas</a></li>
                    <li class="breadcrumb-item active"><?= $id > 0 ? 'Edição' : 'Lançamento Manual' ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if (isset($error)): ?>
         <div class="alert alert-danger border-0 shadow-sm"><i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- AI OCR SECTION -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; background: linear-gradient(135deg, #0A2647 0%, #205295 100%);">
                <div class="card-body p-4 text-white">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 class="fw-bold mb-1"><i class="fas fa-magic me-2"></i>Preenchimento Inteligente com IA</h5>
                            <p class="small mb-0 opacity-75">Faça upload do PDF ou imagem da nota para preencher o formulário automaticamente.</p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <input type="file" id="aiFiscalNote" class="d-none" accept=".pdf, .jpg, .jpeg, .png">
                            <button type="button" class="btn btn-light fw-bold px-4" onclick="document.getElementById('aiFiscalNote').click()">
                                <i class="fas fa-upload me-2 text-primary"></i>Importar Nota
                            </button>
                        </div>
                    </div>
                    
                    <!-- AI Status -->
                    <div id="aiLoading" class="mt-3 d-none">
                        <div class="d-flex align-items-center gap-3">
                            <div class="spinner-border spinner-border-sm text-light"></div>
                            <span class="small">Analisando documento com OCR...</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
                <div class="card-body p-4 p-md-5">
                    <form id="fiscalForm" method="POST">
                        <div class="row g-4">
                            <!-- Tipo & Numero -->
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold">Tipo de Operação <span class="text-danger">*</span></label>
                                <select name="tipo" class="form-select form-control-lg bg-light border-0" required>
                                    <option value="saida" <?= $tipo_get == 'saida' ? 'selected' : '' ?>>Saída (Venda)</option>
                                    <option value="entrada" <?= $tipo_get == 'entrada' ? 'selected' : '' ?>>Entrada (Compra)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold">Número da Nota <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg bg-light border-0 font-monospace" name="numero" id="numero" value="<?= htmlspecialchars($nota['numero'] ?? '') ?>" required placeholder="000000000">
                            </div>

                            <!-- Emitente/Dest -->
                            <div class="col-md-12">
                                <label class="form-label text-muted small fw-bold"><span id="lblEntidade"><?= $tipo_get == 'saida' ? 'Cliente Destinatário' : 'Fornecedor Emitente' ?></span> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg bg-light border-0" name="emitente_destinatario" id="emitente_destinatario" value="<?= htmlspecialchars($nota['emitente_destinatario'] ?? '') ?>" required>
                            </div>

                            <!-- Data e Status -->
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold">Data de Emissão <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-lg bg-light border-0" name="data_emissao" id="data_emissao" value="<?= htmlspecialchars($nota['data_emissao'] ?? date('Y-m-d')) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select form-control-lg bg-light border-0" required>
                                    <option value="autorizada" <?= isset($nota['status']) && $nota['status'] == 'autorizada' ? 'selected' : '' ?>>Autorizada</option>
                                    <option value="pendente" <?= isset($nota['status']) && $nota['status'] == 'pendente' ? 'selected' : '' ?>>Pendente / Contingência</option>
                                    <option value="cancelada" <?= isset($nota['status']) && $nota['status'] == 'cancelada' ? 'selected' : '' ?>>Cancelada / Denegada</option>
                                </select>
                            </div>

                            <!-- Valores -->
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold">Valor Total (R$) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control form-control-lg bg-light border-0 font-monospace fw-bold text-navy" name="valor_total" id="valor_total" value="<?= htmlspecialchars($nota['valor_total'] ?? '') ?>" required placeholder="0.00">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold">Valor dos Impostos (R$)</label>
                                <input type="number" step="0.01" class="form-control form-control-lg bg-light border-0 font-monospace text-danger" name="valor_impostos" id="valor_impostos" value="<?= htmlspecialchars($nota['valor_impostos'] ?? '') ?>" placeholder="0.00">
                            </div>

                            <!-- Chave Acesso -->
                            <div class="col-md-12">
                                <label class="form-label text-muted small fw-bold">Chave de Acesso Sefaz (44 dígitos)</label>
                                <input type="text" maxlength="44" class="form-control form-control-lg bg-light border-0 font-monospace" name="chave_acesso" id="chave_acesso" value="<?= htmlspecialchars($nota['chave_acesso'] ?? '') ?>" placeholder="Ex: 3523XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX">
                            </div>
                        </div>

                        <div class="mt-5 pt-4 border-top d-flex justify-content-end gap-2">
                            <a href="notas.php" class="btn btn-secondary rounded-pill px-4 py-2">Cancelar</a>
                            <button type="submit" class="btn btn-trust-primary rounded-pill px-5 py-2 fw-bold" style="background-color: #0A2647; color: white;">
                                <?= $id > 0 ? 'Atualizar Nota' : 'Lançar Nota' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Dinamic label change
    document.querySelector('select[name="tipo"]').addEventListener('change', function() {
        document.getElementById('lblEntidade').innerText = this.value === 'saida' ? 'Cliente Destinatário' : 'Fornecedor Emitente';
    });

    // AI OCR Integration
    const fileInput = document.getElementById('aiFiscalNote');
    const loading = document.getElementById('aiLoading');
    
    fileInput.addEventListener('change', function() {
        if (!this.files.length) return;
        
        const file = this.files[0];
        const formData = new FormData();
        formData.append('file', file);
        
        loading.classList.remove('d-none');
        
        fetch('../../../api/process_invoice_upload.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            loading.classList.add('d-none');
            if (data.success) {
                const info = data.data;
                // Fill fields
                if (info.numero_nota) document.getElementById('numero').value = info.numero_nota;
                if (info.data_emissao) document.getElementById('data_emissao').value = info.data_emissao;
                if (info.valor_total) document.getElementById('valor_total').value = parseFloat(info.valor_total);
                
                // Smart entity filling (Name or Recipient)
                const tipo = document.querySelector('select[name="tipo"]').value;
                if (tipo === 'saida' && info.nome_destinatario) {
                    document.getElementById('emitente_destinatario').value = info.nome_destinatario;
                } else if (tipo === 'entrada' && info.nome_fornecedor) {
                    document.getElementById('emitente_destinatario').value = info.nome_fornecedor;
                }
                
                if (info.chave_acesso_nfde) document.getElementById('chave_acesso').value = info.chave_acesso_nfde;
                
                // Show success feedback
                alert('Dados extraídos com sucesso via IA! Por favor, confira os campos antes de salvar.');
            } else {
                alert('Erro na leitura da IA: ' + (data.error || 'Desconhecido'));
            }
        })
        .catch(err => {
            loading.classList.add('d-none');
            alert('Erro de conexão com o servidor de IA.');
            console.error(err);
        });
    });
</script>

<style>
    .text-navy { color: #0A2647; }
    .form-control-lg { font-size: 1rem; padding: 0.75rem 1rem; border-radius: 8px; }
    .font-monospace { font-family: 'Courier New', Courier, monospace; letter-spacing: 0.5px; }
    .form-control:focus, .form-select:focus { box-shadow: 0 0 0 0.25rem rgba(10, 38, 71, 0.1); }
</style>

<?php require_once __DIR__ . '/../../../includes/rodape.php'; ?>
