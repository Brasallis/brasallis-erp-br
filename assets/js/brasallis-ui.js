/**
 * BRASALLIS UI v11.0 - ZEN ELITE ENGINE
 * Interatividade: Sidebar Rail, App Hub Overlay e Minimalismo Radical.
 */

document.addEventListener('DOMContentLoaded', function() {
    const hub = document.getElementById('brasallisAppHub');
    const mainContent = document.querySelector('.brasallis-main');
    
    // --- 1. APP HUB ENGINE ---
    window.toggleBrasallisHub = function() {
        if (!hub) return;
        
        const isActive = hub.classList.contains('active');
        if (isActive) {
            hub.classList.remove('active');
            setTimeout(() => hub.style.display = 'none', 300);
            if (mainContent) mainContent.style.filter = 'none';
        } else {
            hub.style.display = 'block';
            setTimeout(() => hub.classList.add('active'), 10);
            if (mainContent) mainContent.style.filter = 'blur(10px) brightness(0.9)';
        }
    };

    // Close hub on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && hub && hub.classList.contains('active')) {
            toggleBrasallisHub();
        }
    });

    // --- 2. OMNI-SEARCH ENGINE ---
    const searchInput = document.getElementById('globalSearchInput');
    const searchContainer = document.getElementById('searchContainer');
    
    if (searchInput && searchContainer) {
        searchInput.addEventListener('focus', () => searchContainer.classList.add('focused'));
        searchInput.addEventListener('blur', () => searchContainer.classList.remove('focused'));
        
        // Shortcut (/)
        document.addEventListener('keydown', (e) => {
            const isInput = ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName);
            if (e.key === '/' && !isInput) {
                e.preventDefault();
                searchInput.focus();
            }
        });
    }

    console.log('Brasallis Zen Elite Engine v11.0 Active.');
});
