/**
 * Gestor Inteligente - Main Scripts
 * Efeito Ripple
 */
document.addEventListener('DOMContentLoaded', function() {
    // Aplica o efeito ripple em botões e itens de lista que tenham a classe .ripple-effect
    const rippleElements = document.querySelectorAll('.btn, .list-group-item-action');

    rippleElements.forEach(el => {
        el.classList.add('ripple-effect'); // Garante que a classe base está lá
        
        el.addEventListener('click', function(e) {
            // Previne múltiplos ripples se o evento borbulhar
            if (e.target.closest('.ripple-effect') !== this) {
                return;
            }

            const rect = this.getBoundingClientRect();
            const ripple = document.createElement('span');
            const diameter = Math.max(this.clientWidth, this.clientHeight);
            const radius = diameter / 2;

            ripple.className = 'ripple';
            ripple.style.width = ripple.style.height = `${diameter}px`;
            ripple.style.left = `${e.clientX - rect.left - radius}px`;
            ripple.style.top = `${e.clientY - rect.top - radius}px`;
            
            // Remove ripple antigo se houver
            const oldRipple = this.querySelector('.ripple');
            if (oldRipple) {
                oldRipple.remove();
            }
            
            this.appendChild(ripple);
        });
    });
});
