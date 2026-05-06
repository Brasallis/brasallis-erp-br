<?php
// admin/onboarding.php v3.0 - O ARQUITETO BRASALLIS (PLAN SELECT EDITION)
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../includes/funcoes.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'super_admin')) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../includes/planos_config.php';

$empresa_id = $_SESSION['empresa_id'] ?? 1;

$conn = connect_db();
$stmt = $conn->prepare("SELECT ai_plan, onboarding_completed FROM empresas WHERE id = ?");
$stmt->execute([$empresa_id]);
$empresa = $stmt->fetch();

function hexToRgb($hex) {
    $hex = str_replace("#", "", $hex);
    if(strlen($hex) == 3) {
        $r = hexdec(substr($hex,0,1).substr($hex,0,1));
        $g = hexdec(substr($hex,1,1).substr($hex,1,1));
        $b = hexdec(substr($hex,2,1).substr($hex,2,1));
    } else {
        $r = hexdec(substr($hex,0,2));
        $g = hexdec(substr($hex,2,2));
        $b = hexdec(substr($hex,4,2));
    }
    return "$r, $g, $b";
}

$plano_inicial = $_GET['plan'] ?? 'vision';
$billing_inicial = $_GET['billing'] ?? ($_SESSION['user_billing'] ?? 'mensal');

// Consumir Configuração Centralizada
$central_config = get_planos_config();
$plan_config = $central_config['planos'];
$all_modules = $central_config['modulos_info'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurando seu Brasallis Hub</title>
    <link rel="icon" type="image/png" href="/assets/img/pureza.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../assets/css/material_system.css" rel="stylesheet">
    <style>
        :root { --primary: var(--m3-primary); --bg-airy: #f8fafc; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-airy); padding-bottom: 80px; }
        h1, h2, h3 { font-family: 'Outfit', sans-serif; }
        .wizard-step { display: none; }
        .wizard-step.active { display: block; animation: fadeIn 0.4s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .btn-nav { border-radius: 50px; padding: 0.8rem 2.5rem; font-weight: 700; }

        /* Master Billing Switcher (Top Control) */
        .master-billing-control {
            background: #f2f2f7;
            padding: 5px;
            border-radius: 20px;
            display: inline-flex;
            gap: 5px;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 40px;
        }

        .master-pill {
            border: none;
            background: transparent;
            padding: 10px 20px;
            font-size: 0.8rem;
            font-weight: 700;
            color: #86868b;
            border-radius: 15px;
            transition: all 0.4s ease;
            cursor: pointer;
        }

        .master-pill.active {
            background: #fff;
            color: #000;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }

        /* Elite Glass Cards */
        .card-plan {
            background: #ffffff;
            border: 1px solid rgba(0,0,0,0.06);
            border-radius: 35px;
            padding: 40px 25px;
            transition: all 0.5s cubic-bezier(0.23, 1, 0.32, 1);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
            height: 100%;
        }

        .card-plan:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 60px rgba(0,0,0,0.05);
            border-color: var(--plan-color);
        }

        .card-plan.selected {
            border: 2px solid var(--plan-color);
            background: #fff;
            box-shadow: 0 20px 40px rgba(var(--plan-color-rgb), 0.12);
        }

        .plan-potencial {
            font-size: 0.85rem;
            color: #86868b;
            line-height: 1.5;
            margin-bottom: 25px;
            font-style: italic;
        }

        .hero-icon-box {
            width: 60px;
            height: 60px;
            border-radius: 18px;
            background: #f5f5f7;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 20px;
        }

        .price-value {
            font-family: 'Outfit', sans-serif;
            font-size: 2.5rem;
            line-height: 1;
            font-weight: 900;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .pulse-sync { animation: pulseSync 0.4s ease; }
        @keyframes pulseSync {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .fw-black { font-weight: 900; }
        .ls-1 { letter-spacing: -1px; }

        .card-module { 
            border-radius: 20px; border: 2px solid #edf2f7; transition: all 0.3s; cursor: pointer; background: #fff;
        }
        .card-module.selected { border-color: #000; background: #f8fafc; }
        .card-module.locked { opacity: 0.5; cursor: not-allowed; filter: grayscale(1); background: #f1f5f9; }
        
        .progress { height: 8px; border-radius: 10px; background: #e2e8f0; margin-bottom: 40px; }
        .progress-bar { background: #000; border-radius: 10px; transition: width 0.4s ease; }
        
        .icon-box { width: 50px; height: 50px; border-radius: 14px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; margin-bottom: 1rem; transition: 0.3s; }
        .selected .icon-box { background: #000; color: #fff; }
    </style>
</head>
<body>

<div class="container py-5" style="max-width: 1000px;">
    <div class="progress">
        <div class="progress-bar" id="wizardProgress" style="width: 25%"></div>
    </div>

    <form id="onboardingForm" action="processar_onboarding.php" method="POST">
        <input type="hidden" name="empresa_id" value="<?= $empresa_id ?>">
        
        <!-- STEP 1: ESCOLHA DO PLANO -->
        <div class="wizard-step active" id="step1">
            <div class="text-center mb-5">
                <h1 class="fw-bold display-5" style="letter-spacing: -2px;">Escolha o seu <span class="text-primary">Plano</span></h1>
                <p class="text-secondary fs-5">Selecione o pacote que melhor atende sua empresa agora.</p>
            </div>
            
            <div class="text-center mb-4">
                <div class="master-billing-control">
                    <button type="button" class="master-pill <?= $billing_inicial === 'mensal' ? 'active' : '' ?>" data-period="mensal" onclick="masterSyncPricing('mensal', this)">Mensal</button>
                    <button type="button" class="master-pill <?= $billing_inicial === 'semestral' ? 'active' : '' ?>" data-period="semestral" onclick="masterSyncPricing('semestral', this)">6 Meses</button>
                    <button type="button" class="master-pill <?= $billing_inicial === 'anual' ? 'active' : '' ?>" data-period="anual" onclick="masterSyncPricing('anual', this)">Anual</button>
                    <button type="button" class="master-pill <?= $billing_inicial === 'bienal' ? 'active' : '' ?>" data-period="bienal" onclick="masterSyncPricing('bienal', this)">2 Anos</button>
                </div>
            </div>

            <input type="hidden" name="plan" id="planInput" value="<?= $plano_inicial ?>">
            <input type="hidden" name="billing" id="billingInput" value="<?= $billing_inicial ?>">

            <div class="row g-4 mb-5">
                <?php foreach ($plan_config as $key => $p): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card-plan plan-item <?= $key === $plano_inicial ? 'selected' : '' ?>" 
                         onclick="selectPlan('<?= $key ?>', this)" 
                         style="--plan-color: <?= $p['color'] ?>; --plan-color-rgb: <?= hexToRgb($p['color']) ?>">
                        
                        <div class="hero-icon-box" style="color: <?= $p['color'] ?>;">
                            <i class="fas <?= $key === 'foundation' ? 'fa-seedling' : ($key === 'vision' ? 'fa-brain' : 'fa-fort-awesome') ?>"></i>
                        </div>
                        
                        <span class="x-small fw-bold text-muted text-uppercase ls-1 mb-1" style="font-size: 0.6rem;"><?= $p['nome'] ?></span>
                        <h4 class="fw-black ls-1 mb-2" style="font-size: 1.5rem;"><?= $key === 'foundation' ? 'Organização' : ($key === 'vision' ? 'Inteligência' : 'Governança') ?></h4>
                        
                        <p class="plan-potencial">
                            <?= $key === 'foundation' ? '"Assuma o controle total do seu estoque e vendas com precisão."' : ($key === 'vision' ? '"Onde a IA antecipa o seu futuro financeiro."' : '"Poder total para redes e governança de elite."') ?>
                        </p>

                        <div class="price-container mb-4">
                            <div class="d-flex align-items-baseline justify-content-center">
                                <span class="text-muted small fw-bold me-1">R$</span>
                                <span class="price-value price-display text-dark" id="price-<?= $key ?>">
                                    <?= number_format($p['precos'][$billing_inicial] / ($billing_inicial === 'mensal' ? 1 : ($billing_inicial === 'semestral' ? 6 : ($billing_inicial === 'anual' ? 12 : 24))), 2, ',', '.') ?>
                                </span>
                                <span class="text-muted small ms-1">/mês*</span>
                            </div>
                        </div>

                        <div class="benefits-list w-100 mt-auto" style="text-align: left; padding: 0 15px;">
                            <div class="benefit-item d-flex align-items-start gap-2 mb-2">
                                <i class="fas fa-check-circle text-success mt-1" style="font-size: 0.8rem;"></i>
                                <div class="benefit-info">
                                    <div class="fw-bold text-dark" style="font-size: 0.75rem;"><?= $p['users_limit'] > 100 ? 'Ilimitados' : $p['users_limit'] . ' Usuários' ?></div>
                                    <div class="text-muted" style="font-size: 0.65rem;">Acesso total para sua equipe.</div>
                                </div>
                            </div>
                            <div class="benefit-item d-flex align-items-start gap-2">
                                <i class="fas fa-check-circle text-success mt-1" style="font-size: 0.8rem;"></i>
                                <div class="benefit-info">
                                    <div class="fw-bold text-dark" style="font-size: 0.75rem;"><?= count($p['modulos']) ?> Módulos Ativos</div>
                                    <div class="text-muted" style="font-size: 0.65rem;">Ferramentas essenciais desbloqueadas.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center">
                <button type="button" class="btn btn-dark btn-nav" onclick="nextStep(2)">Próximo: Ferramentas <i class="fas fa-arrow-right ms-2"></i></button>
            </div>
        </div>

        <!-- STEP 2: ESCOLHA DE MÓDULOS -->
        <div class="wizard-step" id="step2">
            <div class="text-center mb-5">
                <h1 class="fw-bold display-5" style="letter-spacing: -2px;">Ative suas <span class="text-primary">Ferramentas</span></h1>
                <p class="text-secondary fs-5">Módulos em cinza não estão disponíveis no plano selecionado.</p>
            </div>

            <div class="row g-3 mb-5" id="modulesGrid">
                <?php foreach ($all_modules as $key => $m): ?>
                <div class="col-md-4 col-sm-6 module-wrapper" data-module="<?= $key ?>">
                    <div class="card-module p-3 h-100 module-item" onclick="toggleModule('<?= $key ?>', this)">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="icon-box"><i class="fas <?= $m['icon'] ?>"></i></div>
                            <input type="checkbox" name="modules[]" value="<?= $key ?>" id="check_<?= $key ?>" class="form-check-input d-none">
                            <i class="fas fa-check-circle text-success check-icon d-none"></i>
                            <i class="fas fa-lock text-muted lock-icon d-none"></i>
                        </div>
                        <h6 class="fw-bold mb-1"><?= $m['nome'] ?></h6>
                        <p class="x-small text-muted mb-0" style="font-size: 0.75rem;"><?= $m['desc'] ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="d-flex justify-content-center gap-3">
                <button type="button" class="btn btn-light btn-nav" onclick="nextStep(1)"><i class="fas fa-arrow-left me-2"></i> Voltar</button>
                <button type="button" class="btn btn-dark btn-nav" onclick="nextStep(3)">Configurar Empresa <i class="fas fa-arrow-right ms-2"></i></button>
            </div>
        </div>

        <!-- STEP 3: SEGMENTO & BRANDING -->
        <div class="wizard-step" id="step3">
            <div class="text-center mb-5">
                <h1 class="fw-bold display-5" style="letter-spacing: -2px;">Sua <span class="text-primary">Identidade</span></h1>
                <p class="text-secondary fs-5">Personalize o Hub com as cores da sua marca.</p>
            </div>

            <div class="row justify-content-center mb-5">
                <div class="col-md-8">
                    <div class="bg-white p-4 rounded-4 border shadow-sm mb-4">
                        <div class="row g-4">
                            <div class="col-12">
                                <label class="form-label fw-bold small text-muted">SEGMENTO DE ATUAÇÃO</label>
                                <select name="segmento" class="form-select form-select-lg border-0 bg-light">
                                    <option value="varejo">Varejo / Loja</option>
                                    <option value="servicos">Prestação de Serviços</option>
                                    <option value="industria">Indústria / Fábrica</option>
                                    <option value="alimentacao">Alimentação / Restaurante</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">COR PRINCIPAL</label>
                                <input type="color" name="branding_color" class="form-control form-control-color w-100 border-0" value="#000000">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">TAMANHO DA EQUIPE</label>
                                <input type="number" name="qtd_funcionarios" class="form-control form-control-lg border-0 bg-light" placeholder="Ex: 5" required>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info border-0 rounded-4">
                        <i class="fas fa-info-circle me-2"></i> Você poderá alterar o logo e as cores avançadas no menu de Configurações após o Onboarding.
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-center gap-3">
                <button type="button" class="btn btn-light btn-nav" onclick="nextStep(2)"><i class="fas fa-arrow-left me-2"></i> Voltar</button>
                <button type="submit" class="btn btn-dark btn-nav" id="btnFinalizar">Explodir de Vender! <i class="fas fa-rocket ms-2"></i></button>
            </div>
        </div>
    </form>
</div>

<script>
    const planConfig = <?= json_encode($plan_config) ?>;

    function selectPlan(plan, el) {
        document.querySelectorAll('.plan-item').forEach(i => i.classList.remove('selected'));
        el.classList.add('selected');
        document.getElementById('planInput').value = plan;
        updateModulesUI();
    }

    function masterSyncPricing(period, btn) {
        // Atualizar estado visual do seletor master
        document.querySelectorAll('.master-pill').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');

        // Atualizar o input global
        document.getElementById('billingInput').value = period;

        // Atualizar os preços em todos os cards
        for (const [pKey, pData] of Object.entries(planConfig)) {
            const display = document.getElementById(`price-${pKey}`);
            if (display) {
                let divisor = 1;
                if (period === 'semestral') divisor = 6;
                if (period === 'anual') divisor = 12;
                if (period === 'bienal') divisor = 24;

                const pricePerMonth = pData.precos[period] / divisor;
                display.innerText = pricePerMonth.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                
                // Feedback visual tátil
                display.classList.remove('pulse-sync');
                void display.offsetWidth; // trigger reflow
                display.classList.add('pulse-sync');
            }
        }
    }

    function updateModulesUI() {
        const selectedPlan = document.getElementById('planInput').value;
        const allowedModules = planConfig[selectedPlan].modulos;
        
        document.querySelectorAll('.module-wrapper').forEach(wrapper => {
            const modKey = wrapper.getAttribute('data-module');
            const card = wrapper.querySelector('.card-module');
            const check = wrapper.querySelector('.form-check-input');
            const lockIcon = wrapper.querySelector('.lock-icon');
            const checkIcon = wrapper.querySelector('.check-icon');

            if (allowedModules.includes(modKey)) {
                card.classList.remove('locked');
                lockIcon.classList.add('d-none');
                // Se for um módulo essencial (estoque, pdv, relatorios), seleciona por padrão
                if (['estoque', 'pdv', 'relatorios'].includes(modKey)) {
                    card.classList.add('selected');
                    check.checked = true;
                    checkIcon.classList.remove('d-none');
                } else {
                    card.classList.remove('selected');
                    check.checked = false;
                    checkIcon.classList.add('d-none');
                }
            } else {
                card.classList.add('locked');
                card.classList.remove('selected');
                check.checked = false;
                lockIcon.classList.remove('d-none');
                checkIcon.classList.add('d-none');
            }
        });
    }

    function toggleModule(key, el) {
        if (el.classList.contains('locked')) {
            alert('Este módulo não faz parte do plano selecionado. Volte ao passo 1 para fazer upgrade.');
            return;
        }
        const check = document.getElementById('check_' + key);
        const checkIcon = el.querySelector('.check-icon');
        
        check.checked = !check.checked;
        el.classList.toggle('selected', check.checked);
        if (check.checked) checkIcon.classList.remove('d-none');
        else checkIcon.classList.add('d-none');
    }

    function nextStep(step) {
        document.querySelectorAll('.wizard-step').forEach(s => s.classList.remove('active'));
        document.getElementById('step' + step).classList.add('active');
        
        const progress = (step / 3) * 100;
        document.getElementById('wizardProgress').style.width = progress + '%';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Inicializar UI de módulos no carregamento
    document.addEventListener('DOMContentLoaded', updateModulesUI);

    document.getElementById('onboardingForm').onsubmit = function() {
        const btn = document.getElementById('btnFinalizar');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Criando seu Ambiente...';
        btn.disabled = true;
    };
</script>
</body>
</html>
