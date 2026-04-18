export function initExamTimer() {
    const timerEl = document.getElementById('exam-timer');
    if (!timerEl) return;

    const examId   = timerEl.dataset.examId;
    const duration = parseInt(timerEl.dataset.duration, 10); // seconds
    const storageKey = `examEndTime_${examId}`;

    let endTime = localStorage.getItem(storageKey);
    if (!endTime) {
        endTime = Date.now() + duration * 1000;
        localStorage.setItem(storageKey, endTime);
    } else {
        endTime = parseInt(endTime, 10);
    }

    let interval;

    const tick = () => {
        const remaining = Math.max(0, Math.floor((endTime - Date.now()) / 1000));
        const m = String(Math.floor(remaining / 60)).padStart(2, '0');
        const s = String(remaining % 60).padStart(2, '0');
        timerEl.textContent = `${m}:${s}`;

        // Color changes
        timerEl.classList.remove('text-gray-900', 'text-yellow-500', 'text-red-500', 'animate-pulse');
        if (remaining <= 60) {
            timerEl.classList.add('text-red-500', 'animate-pulse');
        } else if (remaining <= 120) {
            timerEl.classList.add('text-yellow-500');
        } else {
            timerEl.classList.add('text-gray-900');
        }

        if (remaining <= 0) {
            clearInterval(interval);
            localStorage.removeItem(storageKey);
            const form = document.getElementById('exam-form');
            if (form) form.submit();
        }
    };

    tick();
    interval = setInterval(tick, 1000);

    // Save progress on page unload via sendBeacon
    const form = document.getElementById('exam-form');
    if (form) {
        window.addEventListener('beforeunload', () => {
            const saveUrl = form.dataset.saveProgressUrl;
            if (!saveUrl) return;

            const formData = new FormData(form);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const payload = new URLSearchParams(formData);
            if (csrfToken) payload.append('_token', csrfToken);

            navigator.sendBeacon(saveUrl, payload);
        });
    }
}
