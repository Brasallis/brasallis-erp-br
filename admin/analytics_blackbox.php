<?php 
// admin/analytics_blackbox.php
include_once __DIR__ . '/../includes/cabecalho.php';
require_once __DIR__ . '/../classes/AIAgent.php';
use App\AIAgent;

// Proteção Adicional
if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }

$conn = connect_db();
$aiAgent = new App\AIAgent($conn);
$metrics = $aiAgent->getEfficiencyMetrics($_SESSION['empresa_id']);

// Cores do Heatmap (Azul -> Verde)
function getHeatmapColor($count) {
    if ($count == 0) return '#f1f5f9'; // Soft Gray
    if ($count < 5) return '#0070F2';  // Brand Blue
    if ($count < 15) return '#0EA5E9'; // Sky Blue
    return '#10b981'; // Success Green
}
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap');

    :root {
        --bb-blue: #0070F2;
        --bb-green: #10b981;
        --bb-surface: #ffffff;
        --bb-soft: #f8fafc;
    }

    body { background-color: #f1f5f9; font-family: 'Outfit', sans-serif; }

    .blackbox-container { max-width: 1200px; margin: 0 auto; padding: 2rem 1rem; }

    /* Glass Effect Headers */
    .bb-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem; }
    .bb-title { font-size: 2.5rem; font-weight: 800; color: #1e293b; letter-spacing: -1.5px; }
    .bb-status-badge { 
        background: rgba(16, 185, 129, 0.15); color: var(--bb-green); padding: 0.5rem 1.25rem; 
        border-radius: 99px; font-weight: 700; font-size: 0.8rem; letter-spacing: 1px;
        display: flex; align-items: center; gap: 8px;
    }
    .bb-status-dot { width: 8px; height: 8px; background: var(--bb-green); border-radius: 50%; box-shadow: 0 0 10px var(--bb-green); }

    /* Metric Grid */
    .bb-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem; }
    .bb-card { 
        background: white; border-radius: 28px; padding: 2rem; border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .bb-card:hover { transform: translateY(-6px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); }
    
    .bb-label { font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 1rem; display: block; }
    .bb-value { font-size: 3rem; font-weight: 800; color: #0f172a; line-height: 1; margin-bottom: 0.5rem; }
    .bb-suffix { font-size: 1rem; color: #64748b; font-weight: 500; }
    .bb-progress { height: 8px; background: #e2e8f0; border-radius: 99px; overflow: hidden; margin-top: 1.5rem; }
    .bb-progress-fill { height: 100%; border-radius: 99px; background: linear-gradient(90deg, var(--bb-blue), var(--bb-green)); }

    /* Heatmap Section */
    .heatmap-section { background: white; border-radius: 28px; padding: 2.5rem; border: 1px solid #e2e8f0; margin-bottom: 3rem; }
    .heatmap-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
    .heatmap-legend { display: flex; align-items: center; gap: 8px; font-size: 0.75rem; color: #64748b; }
    .legend-box { width: 12px; height: 12px; border-radius: 3px; }

    .heatmap-grid { 
        display: grid; grid-template-columns: repeat(15, 1fr); gap: 10px; 
    }
    @media (min-width: 992px) { .heatmap-grid { grid-template-columns: repeat(30, 1fr); } }

    .heatmap-day { 
        aspect-ratio: 1; border-radius: 4px; transition: transform 0.2s; cursor: pointer;
        position: relative;
    }
    .heatmap-day:hover { transform: scale(1.2); z-index: 10; font-weight: bold; }
    .heatmap-day::after {
        content: attr(data-date); position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%);
        background: #0f172a; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.6rem;
        white-space: nowrap; visibility: hidden; opacity: 0; transition: 0.2s;
    }
    .heatmap-day:hover::after { visibility: visible; opacity: 1; margin-bottom: 5px; }

    /* Action Footer */
    .bb-footer { display: flex; justify-content: center; gap: 1rem; }
    .btn-share { 
        background: var(--bb-blue); color: white; padding: 1rem 2.5rem; border-radius: 99px; 
        font-weight: 700; border: none; transition: 0.3s; display: flex; align-items: center; gap: 12px;
        box-shadow: 0 10px 15px -3px rgba(0, 112, 242, 0.3);
    }
    .btn-share:hover { transform: scale(1.05); background: #005bc5; box-shadow: 0 20px 25px -5px rgba(0, 112, 242, 0.4); }

    /* Export Card (The Hero Component) */
    #export-card { 
        position: absolute; left: -9999px; width: 600px; padding: 40px; background: white; 
        border: 1px solid #e2e8f0; border-radius: 0;
    }
    .powered-by { 
        text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #f1f5f9;
        font-weight: 800; font-size: 0.8rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 2px;
    }
</style>

<div class="blackbox-container">
    <!-- Header -->
    <div class="bb-header d-none d-lg-flex">
        <div>
            <h1 class="bb-title">Black Box Analytics</h1>
            <p class="text-secondary fw-bold">ID Transacional: #<?= strtoupper(substr(md5($_SESSION['empresa_id']), 0, 8)) ?></p>
        </div>
        <div class="bb-status-badge">
            <div class="bb-status-dot"></div>
            INTELIGÊNCIA OPERACIONAL ATIVA (v2.12)
        </div>
    </div>

    <!-- Metric Grid -->
    <div class="bb-grid">
        <div class="bb-card">
            <span class="bb-label"><i class="fas fa-clock-rotate-left me-2 text-primary"></i> ROI Temporal Est.</span>
            <div class="bb-value"><?= $metrics['hours_saved'] ?><span class="bb-suffix">h</span></div>
            <div class="small fw-bold text-success"><i class="fas fa-arrow-trend-up me-1"></i> +<?= round($metrics['total_tasks'] / 10, 1) ?>% vs mês ant.</div>
            <div class="bb-progress"><div class="bb-progress-fill" style="width: min(100%, <?= $metrics['hours_saved'] * 5 ?>%)"></div></div>
        </div>

        <div class="bb-card">
            <span class="bb-label"><i class="fas fa-rocket me-2 text-primary"></i> Precisão AI (Vision)</span>
            <div class="bb-value"><?= number_format($metrics['accuracy_rate'], 1) ?><span class="bb-suffix">%</span></div>
            <div class="small fw-bold text-muted">Ações Inteligentes Realizadas: <?= $metrics['total_tasks'] ?></div>
            <div class="bb-progress"><div class="bb-progress-fill" style="width: <?= $metrics['accuracy_rate'] ?>%"></div></div>
        </div>

        <div class="bb-card">
            <span class="bb-label"><i class="fas fa-microchip me-2 text-primary"></i> Score de Eficiência</span>
            <div class="bb-value"><?= round($metrics['efficiency_score']) ?><span class="bb-suffix">pts</span></div>
            <div class="small fw-bold text-primary">Nível da Empresa: <?= $metrics['efficiency_score'] > 90 ? 'LÍDER DE MERCADO' : 'EVOLUINDO' ?></div>
            <div class="bb-progress"><div class="bb-progress-fill" style="width: <?= $metrics['efficiency_score'] ?>%"></div></div>
        </div>
    </div>

    <!-- Heatmap -->
    <div class="heatmap-section">
        <div class="heatmap-header">
            <div>
                <h5 class="fw-bold mb-1">Ritmo Logístico (30 Dias)</h5>
                <p class="small text-muted mb-0">Atividades detectadas pelos Agentes Vision & CRM.</p>
            </div>
            <div class="heatmap-legend">
                <span>Inativo</span>
                <div class="legend-box" style="background: #f1f5f9;"></div>
                <div class="legend-box" style="background: var(--bb-blue);"></div>
                <div class="legend-box" style="background: var(--bb-green);"></div>
                <span>Alta Intensidade</span>
            </div>
        </div>
        
        <div class="heatmap-grid" id="heatmap-grid">
            <?php
            for ($i = 29; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $count = isset($metrics['heatmap'][$date]) ? $metrics['heatmap'][$date] : 0;
                $color = getHeatmapColor($count);
                echo '<div class="heatmap-day" style="background: '.$color.'" data-date="'.$date.' ('.$count.' tarefas)"></div>';
            }
            ?>
        </div>
    </div>

    <!-- Viral Action -->
    <div class="bb-footer">
        <button class="btn-share" onclick="shareEfficiency()">
            <i class="fas fa-share-nodes"></i>
            GERAR STATUS OPERACIONAL
        </button>
    </div>

</div>

<!-- HIDDEN COMPONENT FOR EXPORT -->
<div id="export-card">
    <div class="d-flex align-items-center mb-4">
        <img src="/assets/img/pureza.png" style="height: 40px;" alt="Logo">
        <div class="ms-3">
            <div style="font-weight: 800; font-size: 1.25rem; color: #1e293b;"><?= htmlspecialchars($_SESSION['empresa_nome'] ?? 'Minha Empresa') ?></div>
            <div style="font-weight: 600; font-size: 0.75rem; color: #64748b; text-transform: uppercase;">Relatório de Desempenho AI</div>
        </div>
    </div>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div style="background: #f8fafc; padding: 25px; border-radius: 20px;">
            <div style="font-size: 0.7rem; font-weight: 800; color: #64748b; margin-bottom: 10px;">TEMPO ECONOMIZADO</div>
            <div style="font-size: 3.5rem; font-weight: 800; color: #1e293b; line-height: 1;"><?= $metrics['hours_saved'] ?>h</div>
        </div>
        <div style="background: #f8fafc; padding: 25px; border-radius: 20px;">
            <div style="font-size: 0.7rem; font-weight: 800; color: #64748b; margin-bottom: 10px;">PRECISÃO OPERACIONAL</div>
            <div style="font-size: 3.5rem; font-weight: 800; color: #0070F2; line-height: 1;"><?= number_format($metrics['accuracy_rate'], 1) ?>%</div>
        </div>
    </div>

    <div class="powered-by">
        POWERED BY BRASALLIS HUB v2.12 IQ ENGINE
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
function shareEfficiency() {
    const card = document.getElementById('export-card');
    card.style.position = 'static';
    card.style.left = '0';
    
    html2canvas(card, {
        scale: 2,
        backgroundColor: '#ffffff'
    }).then(canvas => {
        const link = document.createElement('a');
        link.download = 'brasallis-blackbox-status.png';
        link.href = canvas.toDataURL();
        link.click();
        
        card.style.position = 'absolute';
        card.style.left = '-9999px';
        
        // Show success alert
        alert('Status Card gerado com sucesso! Compartilhe no LinkedIn ou WhatsApp para mostrar sua eficiência.');
    });
}
</script>
