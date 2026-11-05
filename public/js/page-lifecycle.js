(() => {
    if (window.cfsPageLifecycle) return;
    const controllers = new Set();
    let suspended = false;
    const suspend = () => {
        suspended = true;
        controllers.forEach(controller => controller.abort());
        controllers.clear();
    };
    const resume = () => {
        if (!suspended) return;
        suspended = false;
        window.dispatchEvent(new Event('cfs:page-resumed'));
    };
    window.cfsPageLifecycle = {
        get suspended() { return suspended; },
        controller() {
            const controller = new AbortController();
            if (suspended) controller.abort();
            else controllers.add(controller);
            return controller;
        },
        release: controller => controllers.delete(controller),
        suspend,
        resume,
    };
    document.addEventListener('click', event => {
        if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const link = event.target?.closest?.('a[href]');
        if (!link || link.hasAttribute('download') || link.closest('[data-export-menu]') || (link.target && link.target !== '_self')) return;
        const url = new URL(link.href, location.href);
        if (!['http:', 'https:'].includes(url.protocol)) return;
        if (url.origin === location.origin && url.pathname === location.pathname && url.search === location.search) return;
        suspend();
    });
    document.addEventListener('submit', event => { if (!event.defaultPrevented) suspend(); });
    window.addEventListener('pagehide', suspend);
    window.addEventListener('pageshow', resume);
})();
