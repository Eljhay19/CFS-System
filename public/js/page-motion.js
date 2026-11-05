(() => {
    if (window.cfsPageMotion) return;
    window.cfsPageMotion = true;
    const reduced = matchMedia('(prefers-reduced-motion: reduce)');
    const easing = 'cubic-bezier(.16,1,.3,1)';
    const animateIn = (element, transform, duration = 520, delay = 0) => {
        if (!element) return;
        const lowMotion = reduced.matches;
        if (lowMotion) {
            element.style.opacity = '1';
            element.style.transform = 'none';
            return;
        }
        const fromTransform = transform;
        const timing = { duration, delay, easing, fill: 'both' };
        const frames = [{ opacity: 0, transform: fromTransform }, { opacity: 1, transform: 'none' }];
        if (typeof element.animate === 'function') {
            element.animate(frames, timing);
            return;
        }
        element.style.opacity = '0';
        element.style.transform = fromTransform;
        element.style.transition = `opacity ${timing.duration}ms ${timing.easing}, transform ${timing.duration}ms ${timing.easing}`;
        window.setTimeout(() => requestAnimationFrame(() => {
            element.style.opacity = '1';
            element.style.transform = 'none';
        }), timing.delay);
    };
    const show = () => {
        const content = document.querySelector('main.container');
        if (!content) return;
        if (document.body.classList.contains('admin-screen')) {
            const workspace = content.querySelector('.admin-workspace');
            if (!workspace) return;
            animateIn(workspace, 'translateY(8px)', 260);
            return;
        }
        animateIn(content, 'translateY(6px)', 240);
    };
    // Animate the admin workspace as one unit so nested sections do not appear twice.
    show();
    window.addEventListener('pageshow', event => { if (event.persisted) show(); });
    reduced.addEventListener('change', () => {
        if (reduced.matches) {
            document.querySelector('main.container')?.getAnimations?.({ subtree: true }).forEach(animation => animation.cancel());
            show();
        }
    });
})();
