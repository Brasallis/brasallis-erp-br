<?php
/**
 * BRASALLIS DIAGNOSTIC 360 - FULL FLOW EDITION
 * Design: Ultra-Clean, No-Scroll, Mobile-Optimized
 */
?>

<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

<style>
    :root {
        --g-blue: #1a73e8;
        --g-bg: #ffffff;
        --g-text: #202124;
        --g-subtext: #5f6368;
        --g-border: #dadce0;
        --g-shadow: 0 1px 3px rgba(60,64,67,0.3), 0 4px 8px 3px rgba(60,64,67,0.15);
    }

    /* Botão Brasallis Jewel Pulse - Ultra Premium */
    .btn-brasallis-jewel {
        position: relative;
        background: linear-gradient(135deg, #0070f2 0%, #004aab 100%);
        color: #fff !important;
        padding: 18px 42px;
        border-radius: 100px;
        font-weight: 800;
        font-size: 16px;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 14px;
        transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: 0 15px 35px rgba(0, 112, 242, 0.3);
        overflow: hidden;
        animation: jewel-pulse 2.5s infinite;
        letter-spacing: -0.2px;
    }

    /* Brilho da Joia (Shimmer) */
    .btn-brasallis-jewel::before {
        content: '';
        position: absolute;
        top: -50%; left: -100%;
        width: 60%; height: 200%;
        background: linear-gradient(
            to right,
            rgba(255, 255, 255, 0) 0%,
            rgba(255, 255, 255, 0.4) 50%,
            rgba(255, 255, 255, 0) 100%
        );
        transform: rotate(25deg);
        animation: jewel-shimmer 3s infinite;
    }

    @keyframes jewel-shimmer {
        0% { left: -100%; }
        30% { left: 150%; }
        100% { left: 150%; }
    }

    @keyframes jewel-pulse {
        0% { transform: scale(1); box-shadow: 0 15px 35px rgba(0, 112, 242, 0.3); }
        50% { transform: scale(1.03); box-shadow: 0 20px 50px rgba(0, 112, 242, 0.5); }
        100% { transform: scale(1); box-shadow: 0 15px 35px rgba(0, 112, 242, 0.3); }
    }

    .btn-brasallis-jewel:hover {
        transform: translateY(-4px) scale(1.05);
        filter: brightness(1.1);
    }

    @keyframes logo-glow {
        0%, 100% { 
            filter: brightness(0) invert(1) drop-shadow(0 0 4px rgba(255,255,255,0.6));
        }
        50% { 
            filter: brightness(0) invert(1) drop-shadow(0 0 12px rgba(255,255,255,1)) drop-shadow(0 0 20px rgba(180,220,255,0.8));
        }
    }

    @keyframes logo-glow-icon {
        0%, 100% { 
            color: rgba(255, 255, 255, 0.85);
            text-shadow: 0 0 4px rgba(255,255,255,0.4);
        }
        50% { 
            color: #ffffff;
            text-shadow: 0 0 10px rgba(255,255,255,0.9), 0 0 20px rgba(180,220,255,0.7);
        }
    }

    .iq-sparkle {
        background: linear-gradient(135deg, #4285f4, #9b72cb, #d96570);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-size: 18px;
    }

    /* Widget Flutuante — page scrolls freely behind */
    .g-diag-widget {
        position: fixed;
        bottom: 32px;
        right: 32px;
        width: 380px;
        max-height: 90vh;
        z-index: 10000;
        opacity: 0;
        visibility: hidden;
        transform: translateY(20px) scale(0.95);
        transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
    }

    .g-diag-widget.active {
        opacity: 1;
        visibility: visible;
        transform: translateY(0) scale(1);
    }

    .g-diag-surface {
        background: var(--g-bg);
        border-radius: 20px;
        box-shadow: 0 8px 30px rgba(60,64,67,0.25), 0 2px 8px rgba(60,64,67,0.15);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        max-height: 90vh;
    }

    /* Cabeçalho com gradiente sutil e botão fechar destacado */
    .g-diag-header {
        padding: 16px 20px 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8f9fa;
        border-bottom: 1px solid #e8eaed;
        flex-shrink: 0;
    }

    .g-diag-header-info {
        display: flex; align-items: center; gap: 10px;
    }

    .g-diag-badge {
        font-size: 11px; font-weight: 700; text-transform: uppercase;
        letter-spacing: 1px; color: var(--g-blue);
        background: #e8f0fe; padding: 3px 10px; border-radius: 100px;
    }

    .g-step-counter {
        font-size: 12px; color: var(--g-subtext); font-weight: 500;
    }

    /* Botão fechar — grande, óbvio, fácil de clicar */
    .g-close-btn {
        width: 34px; height: 34px; border-radius: 50%;
        border: none;
        background: #e8eaed;
        color: #5f6368;
        cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        font-size: 16px;
        transition: all 0.2s;
        flex-shrink: 0;
    }
    .g-close-btn:hover {
        background: #dadce0;
        color: #202124;
        transform: rotate(90deg);
    }

    .g-diag-content {
        padding: 24px 20px 20px;
        overflow-y: auto;
    }

    .g-progress-container {
        height: 3px; background: #e8eaed; border-radius: 2px;
        margin-bottom: 20px; overflow: hidden;
    }
    .g-progress-bar {
        height: 100%; background: var(--g-blue);
        transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .g-question {
        font-size: 18px; font-weight: 600; color: var(--g-text);
        line-height: 1.4; margin-bottom: 6px;
    }
    .g-sub { font-size: 13px; color: var(--g-subtext); margin-bottom: 20px; }

    .g-options { display: flex; flex-direction: column; gap: 6px; }

    .g-opt-btn {
        width: 100%; text-align: left; background: #fff;
        border: 1.5px solid var(--g-border); border-radius: 10px;
        padding: 12px 16px; font-size: 14px; font-weight: 500;
        color: var(--g-text); cursor: pointer;
        transition: all 0.2s;
        display: flex; align-items: center; gap: 12px;
    }
    .g-opt-btn:hover { background: #f8f9fa; border-color: #bdc1c6; transform: translateX(3px); }
    .g-opt-btn.selected { background: #e8f0fe; border-color: var(--g-blue); color: var(--g-blue); }

    .g-footer {
        padding: 12px 20px 16px;
        border-top: 1px solid #f1f3f4;
        display: flex; justify-content: space-between; align-items: center;
        flex-shrink: 0;
    }

    .btn-secondary {
        background: transparent; border: none; padding: 8px 12px;
        border-radius: 6px; color: var(--g-blue); font-weight: 500;
        font-size: 13px; cursor: pointer;
        transition: background 0.2s;
    }
    .btn-secondary:hover { background: #f0f4ff; }

    @media (max-width: 440px) {
        .g-diag-widget { left: 12px; right: 12px; bottom: 16px; width: auto; }
    }
</style>

<div x-data="diagApp()" @keydown.escape.window="close()">
    
    <!-- CTA Brasallis IQ - Diagnóstico Inteligente -->
    <button class="btn-brasallis-jewel" @click="open()">
        <i class="fas fa-brain" style="
            font-size: 20px;
            animation: logo-glow-icon 2.5s ease-in-out infinite;
        "></i>
        <span>Descobrir Plano Ideal</span>
        <i class="fas fa-chevron-right opacity-50" style="font-size: 12px;"></i>
    </button>

    <!-- Widget Flutuante — página scrolla livremente atrás -->
    <div class="g-diag-widget" :class="{'active': show && !showRoadmap}">
        <div class="g-diag-surface">

            <!-- Header: badge + contador + X -->
            <div class="g-diag-header">
                <div class="g-diag-header-info">
                    <span class="g-diag-badge">Diagnóstico IQ</span>
                    <span class="g-step-counter" x-text="step + ' / 3'"></span>
                </div>
                <button class="g-close-btn" @click="close()" title="Fechar (Esc)">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="g-diag-content">
                <!-- Barra de progresso -->
                <div class="g-progress-container">
                    <div class="g-progress-bar" :style="'width: ' + ((step/3)*100) + '%'"></div>
                </div>

                <h2 class="g-question" x-text="steps[step-1].title"></h2>
                <p class="g-sub" x-text="steps[step-1].sub"></p>

                <div class="g-options">
                    <template x-for="opt in steps[step-1].options">
                        <button class="g-opt-btn" :class="{'selected': isSelected(opt.id)}" @click="select(opt.id); setTimeout(() => nextStep(), 250)">
                            <span style="font-size: 18px;" x-text="opt.icon"></span>
                            <span x-text="opt.label"></span>
                        </button>
                    </template>
                </div>
            </div>

            <div class="g-footer">
                <button class="btn-secondary" @click="step > 1 ? step-- : close()">
                    <i class="fas fa-chevron-left me-1"></i>
                    <span x-text="step > 1 ? 'Voltar' : 'Fechar'"></span>
                </button>
                <span style="font-size: 12px; color: var(--g-subtext);">
                    <i class="fas fa-brain me-1" style="color: var(--g-blue);"></i>
                    Brasallis IQ
                </span>
            </div>
        </div>
    </div>

    <!-- Resultados (Roadmap) -->
    <?php include __DIR__ . '/diagnostic_result.php'; ?>

</div>

<script>
    function diagApp() {
        return {
            show: false,
            step: 1,
            showRoadmap: false,
            roadmapData: null,
            form: { segment: null, maturity: null, needs: [] },
            steps: [
                {
                    title: 'Como devemos chamar sua operação hoje?', sub: 'Selecione o setor que melhor descreve seu negócio.',
                    options: [
                        { id: 'varejo', label: 'Varejo & Lojas Físicas', icon: '🛍️' },
                        { id: 'servicos', label: 'Prestação de Serviços', icon: '🤝' },
                        { id: 'industria', label: 'Indústria & Produção', icon: '🏭' },
                        { id: 'gastronomia', label: 'Alimentação & Bares', icon: '🍕' }
                    ]
                },
                {
                    title: 'Qual o tamanho do seu desafio atual?', sub: 'Escolha a fase em que sua empresa se encontra.',
                    options: [
                        { id: 'fundacao', label: 'Estamos começando agora', icon: '🌱' },
                        { id: 'tracao', label: 'Já operamos e queremos crescer', icon: '🚀' },
                        { id: 'escala', label: 'Somos líderes e buscamos eficiência', icon: '🏆' }
                    ]
                },
                {
                    title: 'O que mais tira o seu sono hoje?', sub: 'Selecione o principal gargalo da sua operação.',
                    options: [
                        { id: 'financeiro', label: 'Controle de Dinheiro e Lucro', icon: '💸' },
                        { id: 'estoque', label: 'Bagunça no Estoque e Compras', icon: '📦' },
                        { id: 'vendas', label: 'Dificuldade em Vender Mais', icon: '📈' },
                        { id: 'processos', label: 'Muita tarefa manual e erros', icon: '🤖' }
                    ]
                }
            ],
            open() { 
                this.show = true; 
                // NÃO bloqueia scroll — widget flutua sobre a página
            },
            close() { 
                this.show = false; 
                this.showRoadmap = false; 
            },
            select(id) {
                if (this.step === 1) this.form.segment = id;
                if (this.step === 2) this.form.maturity = id;
                if (this.step === 3) {
                    if (this.form.needs.includes(id)) this.form.needs = this.form.needs.filter(n => n !== id);
                    else this.form.needs.push(id);
                }
            },
            isSelected(id) {
                if (this.step === 1) return this.form.segment === id;
                if (this.step === 2) return this.form.maturity === id;
                if (this.step === 3) return this.form.needs.includes(id);
            },
            canGo() {
                if (this.step === 1) return this.form.segment !== null;
                if (this.step === 2) return this.form.maturity !== null;
                if (this.step === 3) return this.form.needs.length > 0;
            },
            nextStep() {
                if (this.step < 3) this.step++;
                else this.finish();
            },
            async finish() {
                console.log('Gerando Laudo Técnico para:', this.form);
                
                // Em produção, isso seria uma chamada fetch para um controller PHP
                // Aqui simulamos o processamento do DiagnosticService.php refatorado
                
                const score = this.form.maturity === 'escala' ? 89 : (this.form.maturity === 'tracao' ? 62 : 38);
                
                let planName = 'FOUNDATION HUB';
                let planReason = 'Seu perfil indica foco em organização e controle primário. O plano FOUNDATION HUB é a base sólida para eliminar planilhas, organizando seu estoque, PDV e financeiro com a segurança do ecossistema Brasallis.';
                let cta = 'Começar com Foundation Hub';

                if(this.form.maturity === 'escala' || this.form.needs.length > 3) {
                    planName = 'ENTERPRISE ELITE';
                    planReason = 'Sua operação exige governança de alta densidade e IA preditiva em escala. O plano ENTERPRISE ELITE oferece suporte VIP com SLA de 1h, usuários ilimitados e o Brasallis IQ em sua capacidade total de 10Mi tokens.';
                    cta = 'Consultoria Enterprise VIP';
                } else if(this.form.maturity === 'tracao') {
                    planName = 'VISION AI HUB';
                    planReason = 'Para empresas em fase de crescimento acelerado, a automação é vital. O plano VISION AI HUB libera o poder do OCR ilimitado e CRM avançado, processando documentos em milissegundos e escalando sua força de vendas.';
                    cta = 'Ativar Vision AI Hub';
                }

                const moduleMapping = {
                    financeiro: { module: 'Fluxo de Caixa 360', why: 'Garante visibilidade total sobre a saúde financeira, prevenindo quebras de caixa comuns no seu setor.' },
                    estoque: { module: 'Inteligência de Inventário', why: 'Otimiza o capital de giro parado e evita rupturas de estoque através de análise preditiva.' },
                    vendas: { module: 'Dashboard de Performance', why: 'Mapeia a origem das suas receitas e o custo de aquisição (CAC), permitindo investir onde há mais retorno.' },
                    processos: { module: 'Automação Operacional', why: 'Padroniza entregas e reduz falhas humanas, criando uma operação replicável e escalável.' }
                };

                this.roadmapData = {
                    score: score,
                    plan_suggested: planName,
                    plan_reason: planReason,
                    main_modules: this.form.needs.map(n => moduleMapping[n]),
                    sector_bonus: 'Configuração personalizada para o setor de ' + this.form.segment,
                    security_note: 'Este laudo técnico serve como base de segurança para a constituição digital da sua empresa no ecossistema Brasallis.',
                    next_step: cta
                };

                this.showRoadmap = true;
            }
        }
    }
</script>
