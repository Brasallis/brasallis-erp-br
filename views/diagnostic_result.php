<style>
    .roadmap-overlay {
        position: fixed; inset: 0;
        background: #f8f9fa;
        z-index: 10001;
        display: none;
        overflow-y: auto;
        animation: g-fade-up 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .roadmap-overlay.active { display: block; }

    @keyframes g-fade-up {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .g-result-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 60px 24px;
    }

    .g-result-card {
        background: #fff;
        border-radius: 28px;
        padding: 48px;
        box-shadow: 0 1px 2px rgba(60,64,67,0.3), 0 1px 3px 1px rgba(60,64,67,0.15);
    }

    .g-result-header { text-align: center; margin-bottom: 48px; }
    
    .g-score-badge {
        display: inline-flex; align-items: center; gap: 8px;
        background: #e8f0fe; color: #1a73e8;
        padding: 6px 16px; border-radius: 100px;
        font-size: 14px; font-weight: 600; margin-bottom: 24px;
        position: relative; overflow: hidden;
    }

    .g-score-badge::after {
        content: '';
        position: absolute;
        top: 0; left: -100%; width: 50%; height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.6), transparent);
        animation: jewel-shimmer 2s infinite;
    }

    .g-result-title {
        font-size: 32px; font-weight: 500; color: #202124;
        letter-spacing: -0.5px; margin-bottom: 16px;
    }

    .g-plan-highlight {
        background: #f8f9fa; border-radius: 24px;
        padding: 32px; margin-bottom: 40px;
        border-left: 6px solid #1a73e8;
    }

    .g-plan-label {
        font-size: 12px; font-weight: 700; text-transform: uppercase;
        color: #1a73e8; letter-spacing: 1px; margin-bottom: 12px;
        display: block;
    }

    .g-plan-name { font-size: 24px; font-weight: 600; color: #202124; margin-bottom: 12px; }
    .g-plan-desc { font-size: 16px; color: #5f6368; line-height: 1.6; }

    .g-section-title {
        font-size: 18px; font-weight: 600; color: #202124;
        margin-bottom: 24px; padding-bottom: 12px;
        border-bottom: 1px solid #f1f3f4;
    }

    .g-module-list { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    
    .g-module-card {
        border: 1px solid #dadce0; border-radius: 16px; padding: 24px;
        transition: border-color 0.2s;
    }
    .g-module-card:hover { border-color: #1a73e8; }

    .g-module-title { font-weight: 600; font-size: 15px; margin-bottom: 8px; display: block; }
    .g-module-why { font-size: 13px; color: #5f6368; line-height: 1.5; }

    .g-result-footer { margin-top: 48px; text-align: center; }

    .btn-g-primary {
        background: #1a73e8; color: #fff;
        padding: 16px 48px; border-radius: 100px;
        font-weight: 600; font-size: 16px; border: none;
        cursor: pointer; transition: background 0.2s;
        box-shadow: 0 1px 2px rgba(60,64,67,0.3), 0 1px 3px 1px rgba(60,64,67,0.15);
    }
    .btn-g-primary:hover { background: #1765cc; box-shadow: 0 1px 3px 1px rgba(60,64,67,0.2), 0 2px 4px 2px rgba(60,64,67,0.1); }

    .g-close-result {
        position: fixed; top: 24px; right: 24px;
        background: #fff; border: 1px solid #dadce0;
        width: 44px; height: 44px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; z-index: 10002;
    }

    @media (max-width: 600px) {
        .g-module-list { grid-template-columns: 1fr; }
        .g-result-card { padding: 32px 20px; }
        .g-result-title { font-size: 24px; }
    }
</style>

<div class="roadmap-overlay" :class="{'active': showRoadmap}">
    <button class="g-close-result" @click="close()">
        <i class="fas fa-times"></i>
    </button>

    <template x-if="roadmapData">
        <div class="g-result-container">
            <div class="g-result-card">
                
                <div class="g-result-header">
                    <div class="g-score-badge">
                        <i class="fas fa-chart-pie"></i>
                        <span>Score de Prontidão: <span x-text="roadmapData.score + '%'"></span></span>
                    </div>
                    <h1 class="g-result-title">Seu Diagnóstico Brasallis IQ</h1>
                    <p class="text-muted">Análise estratégica processada com base no perfil da sua operação.</p>
                </div>

                <div class="g-plan-highlight">
                    <span class="g-plan-label">Plano Recomendado</span>
                    <h2 class="g-plan-name" x-text="roadmapData.plan_suggested"></h2>
                    <p class="g-plan-desc" x-text="roadmapData.plan_reason"></p>
                </div>

                <div class="g-modules-section">
                    <h3 class="g-section-title">Pilares da sua Implementação</h3>
                    <div class="g-module-list">
                        <template x-for="m in roadmapData.main_modules">
                            <div class="g-module-card">
                                <span class="g-module-title" x-text="m.module"></span>
                                <p class="g-module-why" x-text="m.why"></p>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="g-result-footer">
                    <button class="btn-g-primary" @click="location.reload()" x-text="roadmapData.next_step"></button>
                    <p class="mt-4 small text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Ao avançar, seu ambiente será configurado com as prioridades acima.
                    </p>
                </div>

            </div>
        </div>
    </template>
</div>
