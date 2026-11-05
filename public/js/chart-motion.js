(() => {
    if (window.cfsChartMotion) return;
    window.cfsChartMotion = true;
    const reduced = matchMedia('(prefers-reduced-motion: reduce)');
    const duration = 480;
    const easing = 'cubic-bezier(.18,.76,.24,1)';
    const active = new Set();
    const play = (element, frames, options = {}) => {
        const lowMotion = reduced.matches;
        if (lowMotion) {
            Object.assign(element.style, frames[frames.length - 1] || {});
            return;
        }
        const timing = {
            duration: options.duration ?? duration,
            delay: options.delay ?? 0,
            easing: options.easing ?? easing,
            fill: 'both',
        };
        if (typeof element.animate === 'function') {
            element.animate(frames, timing);
            return;
        }
        const from = frames[0] || {};
        const to = frames[frames.length - 1] || {};
        const properties = Object.keys(to);
        Object.assign(element.style, from);
        element.style.transition = properties.map(property => `${property.replace(/[A-Z]/g, letter => `-${letter.toLowerCase()}`)} ${timing.duration}ms ${timing.easing} ${timing.delay}ms`).join(', ');
        requestAnimationFrame(() => requestAnimationFrame(() => Object.assign(element.style, to)));
    };
    const tween = (element, render, complete = () => {}) => {
        if (reduced.matches) {
            render(1);
            complete();
            return;
        }
        const start = performance.now();
        const job = { frame: 0, finish: () => { cancelAnimationFrame(job.frame); render(1); complete(); active.delete(job); } };
        const draw = now => {
            if (!element.isConnected) { job.finish(); return; }
            const progress = Math.min(1, (now - start) / duration);
            render(1 - Math.pow(1 - progress, 4));
            if (progress < 1) job.frame = requestAnimationFrame(draw); else job.finish();
        };
        active.add(job);
        render(0);
        job.frame = requestAnimationFrame(draw);
    };
    const animate = () => {
        const panel = document.querySelector('[data-live-url]');
        if (!panel) return;
        panel.querySelectorAll('.rating-fill, .feedback-rating-track > span').forEach(element => {
            if (element.dataset.motionReady) return;
            element.dataset.motionReady = '1';
            play(element, [
                { transform: 'scaleX(0)', opacity: .3 },
                { transform: 'scaleX(1)', opacity: 1 },
            ]);
        });
        panel.querySelectorAll('.feedback-kpi-spark b').forEach(element => {
            if (element.dataset.motionReady) return;
            element.dataset.motionReady = '1';
            play(element, [{ transform: 'scaleY(0)' }, { transform: 'scaleY(1)' }]);
        });
        panel.querySelectorAll('.feedback-gauge-arc').forEach(element => {
            if (element.dataset.motionReady) return;
            element.dataset.motionReady = '1';
            play(element, [{ clipPath: 'inset(0 100% 0 0)' }, { clipPath: 'inset(0 0 0 0)' }]);
        });
        panel.querySelectorAll('svg.mini-chart polyline, svg.feedback-mini-line polyline, [data-chart-line]').forEach(line => {
            if (line.dataset.motionReady) return;
            line.dataset.motionReady = '1';
            const length = line.getTotalLength();
            if (length > 0) play(line, [
                { strokeDasharray: `${length} ${length}`, strokeDashoffset: length },
                { strokeDasharray: `${length} ${length}`, strokeDashoffset: 0 },
            ]);
        });
        const bars = [...panel.querySelectorAll('[data-chart-bar]')];
        const dots = [...panel.querySelectorAll('[data-chart-dot]')];
        panel.querySelectorAll('[data-chart-bar], [data-chart-ring], [data-chart-dot]').forEach(element => {
            if (element.dataset.motionReady) return;
            element.dataset.motionReady = '1';
            const start = element.hasAttribute('data-chart-ring') ? 'rotate(-80deg) scale(.94)'
                : element.hasAttribute('data-chart-bar') ? 'scaleY(0)' : 'scale(.5)';
            const delay = element.hasAttribute('data-chart-bar') ? Math.min(bars.indexOf(element) * 16, 112)
                : element.hasAttribute('data-chart-dot') ? Math.min(dots.indexOf(element) * 18, 108) : 0;
            play(element, [{ transform: start, opacity: .25 }, { transform: 'none', opacity: 1 }], { delay });
        });
        panel.querySelectorAll('[data-rating-bucket]').forEach(segment => {
            if (segment.dataset.motionReady) return;
            segment.dataset.motionReady = '1';
            play(segment, [
                { strokeDasharray: '0 100' },
                { strokeDasharray: segment.getAttribute('stroke-dasharray') },
            ]);
        });
        panel.querySelectorAll('.feedback-donut').forEach(donut => {
            if (donut.dataset.motionReady) return;
            donut.dataset.motionReady = '1';
            const ring = document.createElement('i');
            ring.className = 'feedback-donut-motion-ring';
            ring.setAttribute('aria-hidden', 'true');
            ring.style.backgroundImage = getComputedStyle(donut).backgroundImage;
            donut.style.backgroundImage = 'none';
            donut.prepend(ring);
            play(ring, [
                { transform: reduced.matches ? 'rotate(-35deg) scale(.97)' : 'rotate(-90deg) scale(.94)', opacity: .35 },
                { transform: 'rotate(0deg) scale(1)', opacity: 1 },
            ]);
            tween(ring, progress => {
                const mask = `conic-gradient(#000 ${progress * 360}deg, transparent 0deg)`;
                ring.style.maskImage = mask;
                ring.style.webkitMaskImage = mask;
            }, () => { ring.style.maskImage = ''; ring.style.webkitMaskImage = ''; });
        });
    };
    animate();
    window.addEventListener('cfs:panel-updated', animate);
    reduced.addEventListener('change', () => {
        if (reduced.matches) {
            active.forEach(job => job.finish());
            document.querySelector('[data-live-url]')?.getAnimations?.({ subtree: true }).forEach(animation => animation.cancel());
            animate();
        }
    });
    window.addEventListener('pagehide', () => active.forEach(job => job.finish()));
})();
