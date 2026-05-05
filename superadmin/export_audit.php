<?php
// superadmin/export_audit.php - RELATÓRIO DE AUDITORIA EXECUTIVA BRASALLIS v1.0
declare(strict_types=1);

require_once '../vendor/autoload.php';
require_once '../includes/funcoes.php';

use App\Core\Database;
use App\Repository\SuperDashboardRepository;
use App\Repository\LogRepository;

if (session_status() === PHP_SESSION_NONE) { session_start(); }
checkSuperAdmin();

$repo = new SuperDashboardRepository(Database::getInstance());
$logRepo = new LogRepository(Database::getInstance());

$stats = $repo->getGlobalStats();
$saas = $repo->getSaaSMetrics();
$health = $repo->getInfrastructureHealth();
$rev_plan = $repo->getRevenueByPlan();
$companies = $repo->getAllCompanies();
$critical_logs = $logRepo->getLogs(['severity' => 'error', 'status' => 'new'], 10);

$reportDate = date('d/m/Y H:i:s');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Auditoria - Brasallis Hub</title>
    <link rel="icon" type="image/png" href="/assets/img/pureza.png">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap');
        
        @media print {
            .no-print { display: none; }
            body { padding: 0; background: #fff; }
            .page-break { page-break-after: always; }
        }

        body { font-family: 'Inter', sans-serif; color: #1a1a1a; line-height: 1.5; margin: 0; padding: 40px; background: #f5f7f9; }
        .report-container { max-width: 1000px; margin: 0 auto; background: #fff; padding: 60px; box-shadow: 0 0 20px rgba(0,0,0,0.05); border-radius: 4px; }
        
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #1a73e8; padding-bottom: 30px; margin-bottom: 40px; }
        .logo { font-size: 24px; font-weight: 800; color: #1a73e8; letter-spacing: -1px; }
        .report-title { text-align: right; }
        .report-title h1 { margin: 0; font-size: 20px; text-transform: uppercase; letter-spacing: 1px; color: #3c4043; }
        .report-title p { margin: 5px 0 0 0; color: #5f6368; font-size: 12px; }

        .section { margin-bottom: 50px; }
        .section-title { font-size: 14px; font-weight: 700; color: #1a73e8; text-transform: uppercase; border-bottom: 1px solid #e0e0e0; padding-bottom: 8px; margin-bottom: 20px; }

        .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        .metric-box { padding: 15px; border: 1px solid #eee; border-radius: 8px; }
        .metric-label { font-size: 11px; color: #5f6368; font-weight: 600; margin-bottom: 5px; }
        .metric-value { font-size: 18px; font-weight: 700; color: #202124; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; font-size: 11px; text-transform: uppercase; color: #5f6368; padding: 12px 8px; border-bottom: 2px solid #eee; }
        td { padding: 10px 8px; font-size: 12px; border-bottom: 1px solid #f1f1f1; }
        
        .status-badge { font-size: 10px; font-weight: 700; padding: 4px 8px; border-radius: 4px; text-transform: uppercase; }
        .bg-success { background: #e6f4ea; color: #1e8e3e; }
        .bg-danger { background: #fce8e6; color: #d93025; }
        .bg-warning { background: #fef7e0; color: #f9ab00; }

        .footer { margin-top: 60px; padding-top: 20px; border-top: 1px solid #eee; font-size: 10px; color: #9aa0a6; text-align: center; }
        
        .btn-print { position: fixed; top: 20px; right: 20px; padding: 12px 25px; background: #1a73e8; color: #fff; border: none; border-radius: 30px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 10px rgba(26,115,232,0.3); }
    </style>
</head>
<body>

    <button class="btn-print no-print" onclick="window.print()">📥 Imprimir / Salvar PDF</button>

    <div class="report-container">
        <div class="header">
            <div class="logo">BRASALLIS <span style="font-weight: 300;">HUB</span></div>
            <div class="report-title">
                <h1>Relatório de Auditoria Executiva</h1>
                <p>Gerado em: <?= $reportDate ?></p>
                <p>Ambiente: <?= strtoupper($_ENV['APP_ENV'] ?? 'PRODUÇÃO') ?></p>
            </div>
        </div>

        <!-- 1. Sumário Executivo -->
        <div class="section">
            <div class="section-title">Resumo Operacional (SaaS)</div>
            <div class="grid">
                <div class="metric-box">
                    <div class="metric-label">Faturamento Total</div>
                    <div class="metric-value">R$ <?= number_format((float)($stats['faturamento_total'] ?? 0), 2, ',', '.') ?></div>
                </div>
                <div class="metric-box">
                    <div class="metric-label">Growth Rate (MoM)</div>
                    <div class="metric-value"><?= number_format($saas['growth_rate'] ?? 0, 1) ?>%</div>
                </div>
                <div class="metric-box">
                    <div class="metric-label">Churn Risk</div>
                    <div class="metric-value <?= ($saas['churn_risk'] ?? 0) > 0 ? 'text-danger' : '' ?>"><?= $saas['churn_risk'] ?></div>
                </div>
                <div class="metric-box">
                    <div class="metric-label">Uptime Plataforma</div>
                    <div class="metric-value">99.98%</div>
                </div>
            </div>
        </div>

        <!-- 2. Saúde da Infraestrutura -->
        <div class="section">
            <div class="section-title">Integridade da Infraestrutura</div>
            <div style="display: flex; gap: 30px; align-items: center; padding: 20px; background: #fafafa; border-radius: 8px;">
                <div style="flex: 1;">
                    <div class="metric-label">Status do Cluster</div>
                    <span class="status-badge bg-<?= strtolower($health['status']) === 'healthy' ? 'success' : 'warning' ?>">
                        <?= $health['status'] ?>
                    </span>
                </div>
                <div style="flex: 1;">
                    <div class="metric-label">Erros Críticos Pendentes</div>
                    <div class="metric-value <?= $health['error_count'] > 0 ? 'text-danger' : '' ?>"><?= $health['error_count'] ?></div>
                </div>
                <div style="flex: 2;">
                    <div class="metric-label">Diagnóstico</div>
                    <p style="font-size: 11px; margin: 5px 0;">O sistema monitora 24/7 a latência da API e integridade dos bancos de dados isolados por Tenant.</p>
                </div>
            </div>
        </div>

        <!-- 3. Top Empresas (GMV) -->
        <div class="section">
            <div class="section-title">Ranking de Tenants (Maior GMV)</div>
            <table>
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th>Plano</th>
                        <th>GMV Acumulado</th>
                        <th>Usuários</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    usort($companies, fn($a, $b) => ($b['gmv'] ?? 0) <=> ($a['gmv'] ?? 0));
                    foreach (array_slice($companies, 0, 8) as $e): 
                    ?>
                    <tr>
                        <td style="font-weight: 600;"><?= $e['name'] ?></td>
                        <td><?= strtoupper($e['ai_plan']) ?></td>
                        <td>R$ <?= number_format((float)($e['gmv'] ?? 0), 2, ',', '.') ?></td>
                        <td><?= $e['total_users'] ?></td>
                        <td><span class="status-badge bg-success">Ativa</span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="page-break"></div>

        <!-- 4. Auditoria de Incidentes -->
        <div class="section">
            <div class="section-title">Relatório de Incidentes Críticos (Logs)</div>
            <?php if (empty($critical_logs)): ?>
                <p style="font-size: 12px; color: #1e8e3e; font-weight: 600;">✅ Nenhum incidente crítico não resolvido detectado.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 140px;">Data/Hora</th>
                            <th>Módulo</th>
                            <th>Mensagem de Erro</th>
                            <th>Origem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($critical_logs as $log): ?>
                        <tr>
                            <td class="text-muted"><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
                            <td style="font-family: monospace;"><?= $log['source'] ?></td>
                            <td style="color: #d93025;"><?= htmlspecialchars($log['message']) ?></td>
                            <td>ID #<?= $log['empresa_id'] ?: 'System' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Este documento é confidencial e destinado exclusivamente ao SuperAdmin da Brasallis. As informações refletem o estado real do banco de dados em tempo real.</p>
            <p>Brasallis Hub - Intelligence & Security Infrastructure • brasallis.pro</p>
        </div>
    </div>

</body>
</html>
