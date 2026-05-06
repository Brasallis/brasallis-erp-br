<?php
// superadmin/logs.php - BRASALLIS CLOUD LOGGING v4.0 (SRE Edition)
declare(strict_types=1);

require_once '../vendor/autoload.php';
require_once '../includes/funcoes.php';

use App\Core\Database;
use App\Repository\LogRepository;

if (session_status() === PHP_SESSION_NONE) { session_start(); }
checkSuperAdmin();

$repo = new LogRepository(Database::getInstance());

// Filtros
$filters = [
    'severity' => $_GET['severity'] ?? '',
    'status'   => $_GET['status'] ?? 'new', // Default: Hide resolved
    'source'   => $_GET['source'] ?? '',
    'search'   => $_GET['search'] ?? '',
];

$logs = $repo->getLogs($filters);
$stats = $repo->getStats();

require_once 'includes/header.php';
?>

<style>
    :root {
        --google-gray-100: #f8f9fa;
        --google-gray-200: #e8eaed;
        --google-blue: #1a73e8;
        --google-red: #d93025;
        --google-yellow: #f9ab00;
        --google-green: #1e8e3e;
    }

    .log-console { font-family: 'Roboto', sans-serif; background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(60,64,67,.3); }
    .log-row { cursor: pointer; transition: background 0.1s; border-bottom: 1px solid var(--google-gray-200); font-size: 13px; }
    .log-row:hover { background: #f1f3f4; }
    .log-row.resolved { opacity: 0.6; background: #fdfdfd; }
    .log-row.security-event { border-left: 3px solid #d93025; }
    
    .sev-badge { width: 80px; font-weight: 700; font-size: 10px; text-align: center; border-radius: 4px; padding: 2px 4px; display: inline-block; }
    .sev-error { background: #fce8e6; color: var(--google-red); }
    .sev-warning { background: #fef7e0; color: var(--google-yellow); }
    .sev-info { background: #e8f0fe; color: var(--google-blue); }
    
    .source-security { background: #fce8e6; color: #d93025; font-weight: 700; border-radius: 4px; padding: 2px 8px; font-size: 11px; }
    
    .log-source { color: #5f6368; font-family: 'Roboto Mono', monospace; }
    .log-message { color: #202124; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    
    .stat-card { border-left: 4px solid var(--google-blue); padding: 15px; background: #fff; border-radius: 8px; }
    .stat-card.critical { border-left-color: var(--google-red); }
    .stat-card.warning { border-left-color: var(--google-yellow); }
    .stat-card.security { border-left-color: #e37400; }
    
    .auto-refresh-active { color: var(--google-green); font-weight: bold; }
    .auto-refresh-active i { animation: fa-spin 2s infinite linear; }
    
    @keyframes fa-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>

<div class="container-fluid py-4">
    <!-- Header & Summary -->
    <div class="row mb-4 g-3">
        <div class="col-md-3">
            <div class="stat-card shadow-sm">
                <div class="text-muted small text-uppercase fw-bold">Eventos (24h)</div>
                <div class="h3 mb-0"><?= $stats['total_24h'] ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card critical shadow-sm">
                <div class="text-muted small text-uppercase fw-bold">Erros Críticos</div>
                <div class="h3 mb-0 text-danger"><?= $stats['errors_24h'] ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card warning shadow-sm">
                <div class="text-muted small text-uppercase fw-bold">Não Resolvidos</div>
                <div class="h3 mb-0 text-warning"><?= $stats['unresolved'] ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card security shadow-sm" style="cursor:pointer" onclick="updateFilter('source', 'Security')">
                <div class="text-muted small text-uppercase fw-bold">
                    <i class="fas fa-shield-alt me-1" style="color:#e37400;"></i>Ataques Bloqueados
                </div>
                <div class="h3 mb-0" style="color:#e37400;"><?= $stats['security_events'] ?></div>
                <div class="small text-muted mt-1">Clique para filtrar</div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3 bg-white p-3 rounded shadow-sm">
        <div class="d-flex gap-3 align-items-center flex-wrap">
            <h4 class="mb-0"><i class="fas fa-microchip me-2 text-primary"></i>Log Console</h4>
            
            <div class="btn-group btn-group-sm">
                <a href="?status=new" class="btn <?= $filters['status'] == 'new' ? 'btn-primary' : 'btn-outline-secondary' ?>">Pendentes</a>
                <a href="?status=resolved" class="btn <?= $filters['status'] == 'resolved' ? 'btn-primary' : 'btn-outline-secondary' ?>">Resolvidos</a>
                <a href="?status=" class="btn <?= $filters['status'] == '' ? 'btn-primary' : 'btn-outline-secondary' ?>">Todos</a>
            </div>

            <select class="form-select form-select-sm" style="width: 150px;" onchange="updateFilter('severity', this.value)">
                <option value="">Severidade</option>
                <option value="error" <?= $filters['severity'] == 'error' ? 'selected' : '' ?>>Erro</option>
                <option value="warning" <?= $filters['severity'] == 'warning' ? 'selected' : '' ?>>Aviso</option>
                <option value="info" <?= $filters['severity'] == 'info' ? 'selected' : '' ?>>Info</option>
            </select>
        </div>

        <div class="d-flex gap-2">
            <!-- Botão Mágico para IA -->
            <button class="btn btn-sm btn-dark px-3" onclick="copyToAI()">
                <i class="fas fa-robot me-2 text-info"></i>Copiar para Antigravity
            </button>
            <button id="btnRefresh" class="btn btn-sm btn-light border" onclick="location.reload()">
                <i class="fas fa-sync-alt"></i> Atualizar
            </button>
            <div class="form-check form-switch mt-1 ms-2">
                <input class="form-check-input" type="checkbox" id="autoRefresh">
                <label class="form-check-label small" for="autoRefresh">Auto-Refresh</label>
            </div>
            <button class="btn btn-sm btn-outline-success px-3 ms-3" onclick="resolveAll()">Resolver Todos</button>
        </div>
    </div>

    <!-- Hidden text area for copy support -->
    <textarea id="aiCopyArea" style="position: absolute; left: -9999px;"></textarea>

    <!-- Main Table -->
    <div class="log-console overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light small text-uppercase text-muted">
                    <tr>
                        <th style="width: 50px;"></th>
                        <th style="width: 160px;">Timestamp</th>
                        <th style="width: 100px;">Severity</th>
                        <th style="width: 150px;">Source</th>
                        <th>Message</th>
                        <th style="width: 150px;">Empresa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="6" class="text-center p-5 text-muted">Nenhum log encontrado para estes critérios.</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                        <tr class="log-row <?= $log['status'] ?> <?= $log['source'] === 'Security' ? 'security-event' : '' ?>" data-bs-toggle="collapse" data-bs-target="#detail-<?= $log['id'] ?>">
                            <td class="text-center">
                                <?php if ($log['source'] === 'Security'): ?>
                                    <i class="fas fa-shield-alt" style="color:#d93025;"></i>
                                <?php else: ?>
                                    <i class="fas <?= $log['status'] == 'resolved' ? 'fa-check-circle text-success' : 'fa-exclamation-circle text-muted' ?>"></i>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted"><?= date('H:i:s d/m/Y', strtotime($log['created_at'])) ?></td>
                            <td>
                                <span class="sev-badge sev-<?= $log['severity'] ?>"><?= strtoupper($log['severity']) ?></span>
                            </td>
                            <td>
                                <?php if ($log['source'] === 'Security'): ?>
                                    <span class="source-security"><?= htmlspecialchars($log['source']) ?></span>
                                <?php else: ?>
                                    <span class="log-source"><?= htmlspecialchars($log['source']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="log-message"><?= htmlspecialchars($log['message']) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars($log['empresa_nome'] ?: 'SISTEMA') ?></td>
                        </tr>
                        <tr class="collapse" id="detail-<?= $log['id'] ?>">
                            <td colspan="6" class="bg-light p-4">
                                <div class="row">
                                    <div class="col-md-9">
                                        <div class="d-flex justify-content-between mb-2">
                                            <h6 class="fw-bold m-0">DETALHES DO EVENTO</h6>
                                            <span class="badge bg-dark">ID #<?= $log['id'] ?></span>
                                        </div>
                                        <pre class="bg-dark text-white p-3 rounded small" style="max-height: 400px; overflow-y: auto;"><?= htmlspecialchars($log['stack_trace'] ?: 'Sem stack trace.') ?></pre>
                                    </div>
                                    <div class="col-md-3">
                                        <h6 class="fw-bold mb-2">METADADOS</h6>
                                        <div class="card card-body p-2 small mb-3">
                                            <div><b>IP:</b> <?= $log['ip_address'] ?></div>
                                            <div class="text-truncate"><b>URL:</b> <?= $log['url'] ?></div>
                                            <div><b>User ID:</b> <?= $log['user_id'] ?: 'N/A' ?></div>
                                        </div>
                                        
                                        <?php if ($log['status'] == 'new'): ?>
                                            <button class="btn btn-sm btn-primary w-100 mb-2" onclick="resolveLog(<?= $log['id'] ?>)">
                                                <i class="fas fa-check me-2"></i>Marcar como Resolvido
                                            </button>
                                        <?php else: ?>
                                            <div class="alert alert-success p-2 small">
                                                <i class="fas fa-check-double me-1"></i> Resolvido em <?= date('d/m H:i', strtotime($log['resolved_at'])) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function updateFilter(key, value) {
    const url = new URL(window.location.href);
    url.searchParams.set(key, value);
    window.location.href = url.href;
}

function copyToAI() {
    const logs = <?= json_encode($logs) ?>;
    if (logs.length === 0) {
        alert('Não há logs para copiar.');
        return;
    }

    let markdown = "### 🛡️ RELATÓRIO DE ERROS - BRASALLIS HUB\n\n";
    markdown += "Aqui estão os logs mais recentes do sistema para análise:\n\n";

    logs.forEach(log => {
        markdown += `---
**ID:** #${log.id} | **Data:** ${log.created_at}
**Severity:** ${log.severity.toUpperCase()} | **Source:** ${log.source}
**Empresa:** ${log.empresa_nome || 'SISTEMA'} | **URL:** ${log.url || 'N/A'}
**Mensagem:** \`${log.message}\`
**Stack Trace:**
\`\`\`
${log.stack_trace || 'N/A'}
\`\`\`
\n`;
    });

    const copyArea = document.getElementById('aiCopyArea');
    copyArea.value = markdown;
    copyArea.select();
    document.execCommand('copy');

    if (confirm('✅ Logs copiados para o Antigravity!\n\nDeseja marcar todos esses logs como RESOLVIDOS agora? (Isso limpará seu console)')) {
        resolveAll();
    }
}

function resolveLog(id) {
    if (!confirm('Deseja marcar este log como resolvido?')) return;
    
    const formData = new FormData();
    formData.append('id', id);
    formData.append('action', 'resolve');

    fetch('../api/resolve_log.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erro ao resolver log: ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Erro de rede ao tentar resolver o log.');
    });
}

function resolveAll() {
    if (!confirm('Deseja marcar TODOS os logs desta lista como resolvidos?')) return;

    const formData = new FormData();
    formData.append('action', 'resolve_all');

    fetch('../api/resolve_log.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erro ao resolver todos: ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Erro de rede ao tentar resolver todos os logs.');
    });
}

// Auto Refresh Logic
let refreshInterval;
document.getElementById('autoRefresh').addEventListener('change', function() {
    if (this.checked) {
        document.getElementById('btnRefresh').classList.add('auto-refresh-active');
        refreshInterval = setInterval(() => location.reload(), 15000);
    } else {
        document.getElementById('btnRefresh').classList.remove('auto-refresh-active');
        clearInterval(refreshInterval);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
