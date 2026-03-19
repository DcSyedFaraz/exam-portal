export function initConfetti() {
    const el = document.querySelector('[data-show-confetti="true"]');
    if (!el) return;

    const canvas = document.createElement('canvas');
    canvas.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9999';
    document.body.appendChild(canvas);

    const ctx = canvas.getContext('2d');
    canvas.width  = window.innerWidth;
    canvas.height = window.innerHeight;

    const colors = ['#F5C518','#10B981','#3B82F6','#EC4899','#8B5CF6','#EF4444'];
    const pieces = Array.from({ length: 120 }, () => ({
        x:    Math.random() * canvas.width,
        y:    Math.random() * canvas.height - canvas.height,
        r:    Math.random() * 6 + 3,
        d:    Math.random() * 80 + 20,
        color: colors[Math.floor(Math.random() * colors.length)],
        tilt: Math.random() * 10 - 10,
        tiltAngle: 0,
        tiltAngleInc: Math.random() * 0.07 + 0.05,
    }));

    let frame = 0;

    const draw = () => {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        pieces.forEach(p => {
            p.tiltAngle += p.tiltAngleInc;
            p.y += (Math.cos(frame + p.d) + 1 + p.r / 2) / 2;
            p.tilt = Math.sin(p.tiltAngle) * 12;

            ctx.beginPath();
            ctx.lineWidth = p.r / 2;
            ctx.strokeStyle = p.color;
            ctx.moveTo(p.x + p.tilt + p.r / 4, p.y);
            ctx.lineTo(p.x + p.tilt, p.y + p.tilt + p.r / 4);
            ctx.stroke();

            if (p.y > canvas.height) {
                p.y = -20;
                p.x = Math.random() * canvas.width;
            }
        });
        frame++;
        if (frame < 250) {
            requestAnimationFrame(draw);
        } else {
            canvas.remove();
        }
    };

    draw();
}
