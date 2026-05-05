document.addEventListener('DOMContentLoaded', function () {

    const guidedPanel = document.querySelector('.guided-features-panel');
    if (!guidedPanel) return;

    const textSteps = guidedPanel.querySelectorAll('.feature-step');
    const mockupScreens = guidedPanel.querySelectorAll('.mockup-screen');
    const numSteps = textSteps.length;
    const panelHeight = guidedPanel.offsetHeight;
    
    const isMobile = window.matchMedia("(max-width: 991.98px)").matches;

    window.addEventListener('scroll', () => {
        const panelRect = guidedPanel.getBoundingClientRect();
        
        if (panelRect.bottom < 0 || panelRect.top > window.innerHeight) {
            return;
        }

        const scrollProgress = -panelRect.top / (panelHeight - window.innerHeight);

        if (isMobile) {
            // Lógica de alternância para Mobile
            const numSubSteps = numSteps * 2; // Cada etapa tem 2 sub-etapas (visual, texto)
            let currentSubStep = Math.floor(scrollProgress * numSubSteps);
            currentSubStep = Math.max(0, Math.min(numSubSteps - 1, currentSubStep));

            const currentStepIndex = Math.floor(currentSubStep / 2);
            const isVisualStep = currentSubStep % 2 === 0;

            // Ativa a maquete ou o texto, mas não ambos
            mockupScreens.forEach((screen, index) => {
                screen.classList.toggle('active', index === currentStepIndex && isVisualStep);
            });
            textSteps.forEach((step, index) => {
                step.classList.toggle('active', index === currentStepIndex && !isVisualStep);
            });

        } else {
            // Lógica original para Desktop
            let currentStep = Math.floor(scrollProgress * numSteps);
            currentStep = Math.max(0, Math.min(numSteps - 1, currentStep));

            // Ativa o texto e a maquete da etapa atual
            textSteps.forEach((step, index) => {
                step.classList.toggle('active', index === currentStep);
            });
            mockupScreens.forEach((screen, index) => {
                screen.classList.toggle('active', index === currentStep);
            });
        }
    });

    // Lógica do Gráfico (não alterada)
    const resultsChartCanvas = document.getElementById('resultsChart');
    if (resultsChartCanvas) {
        // ... (código do gráfico)
    }
});
