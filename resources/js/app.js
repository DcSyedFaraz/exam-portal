import './bootstrap';
import { initSidebar }   from './sidebar';
import { initExamTimer } from './exam-timer';
import { initConfetti }  from './confetti';

document.addEventListener('DOMContentLoaded', () => {

    // Sidebar toggle
    initSidebar();

    // Exam timer (only on take-exam page)
    initExamTimer();

    // Confetti (only on result page if passed)
    initConfetti();

    // Page fade-in animation
    const main = document.querySelector('.main-content');
    if (main) {
        requestAnimationFrame(() => {
            main.classList.remove('opacity-0', 'translate-y-2');
            main.classList.add('opacity-100', 'translate-y-0');
        });
    }

    // Toast auto-dismiss
    const toast = document.getElementById('toast-message');
    if (toast) {
        setTimeout(() => {
            toast.style.transition = 'opacity 0.4s, transform 0.4s';
            toast.style.opacity    = '0';
            toast.style.transform  = 'translateX(120%)';
            setTimeout(() => toast.remove(), 400);
        }, 3000);
    }

    // Animated stat counters
    document.querySelectorAll('[data-count-to]').forEach(el => {
        const target = parseInt(el.dataset.countTo, 10);
        if (isNaN(target) || target === 0) { el.textContent = '0'; return; }

        let current = 0;
        const duration = 800; // ms
        const step     = target / (duration / 16);

        const timer = setInterval(() => {
            current = Math.min(current + step, target);
            el.textContent = Math.floor(current);
            if (current >= target) {
                el.textContent = target;
                clearInterval(timer);
            }
        }, 16);
    });

});
