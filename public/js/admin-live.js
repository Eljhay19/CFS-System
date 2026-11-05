(() => {
    let panel = document.querySelector('[data-live-url]');
    if (!panel || window.cfsAdminLive) return;
    window.cfsAdminLive = true;
    const lifecycle = window.cfsPageLifecycle;
    let revision = panel.dataset.liveRevision;
    let busy = false;
    const refresh = async () => {
        if (busy || document.hidden || lifecycle?.suspended) return;
        busy = true;
        try {
            panel = document.querySelector('[data-live-url]');
            if (!panel) return;
            const controller = lifecycle?.controller() || new AbortController();
            const timeout = setTimeout(() => controller.abort(), 10000);
            let response;
            try {
                response = await fetch(panel.dataset.liveUrl, { cache: 'no-store', signal: controller.signal, headers: { Accept: 'application/json' } });
            } finally { clearTimeout(timeout); lifecycle?.release(controller); }
            if (lifecycle?.suspended) return;
            if (!response.ok) return;
            const data = await response.json();
            if (revision !== data.revision) {
                if (panel.querySelector('[data-filters-dirty="1"]')) return;
                if (panel.contains(document.activeElement) && document.activeElement.matches('input,select,textarea')) return;
                const pageController = lifecycle?.controller() || new AbortController();
                const pageTimeout = setTimeout(() => pageController.abort(), 10000);
                let page;
                try {
                    page = await fetch(location.href, { cache: 'no-store', signal: pageController.signal, headers: { Accept: 'text/html' } });
                } finally { clearTimeout(pageTimeout); lifecycle?.release(pageController); }
                if (lifecycle?.suspended) return;
                if (!page.ok) return;
                const html = await page.text();
                if (lifecycle?.suspended) return;
                const replacement = new DOMParser().parseFromString(html, 'text/html').querySelector('[data-live-url]');
                if (!replacement) return;
                if (panel.querySelector('[data-filters-dirty="1"]') || (panel.contains(document.activeElement) && document.activeElement.matches('input,select,textarea'))) return;
                const scrollTop = panel.scrollTop;
                const nextPanel = document.importNode(replacement, true);
                panel.replaceWith(nextPanel);
                panel = nextPanel;
                panel.scrollTop = scrollTop;
                revision = panel.dataset.liveRevision;
                window.dispatchEvent(new Event('cfs:panel-updated'));
                return;
            }
            revision = data.revision;
        } catch (error) { /* Keep existing content visible while offline. */ }
        finally { busy = false; }
    };
    // The server already rendered the initial dashboard. Let it settle before
    // the first live poll so startup cannot replace the panel during its entrance.
    setInterval(refresh, 3000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
    window.addEventListener('focus', refresh);
    window.addEventListener('cfs:notifications-updated', refresh);
    window.addEventListener('cfs:page-resumed', refresh);
})();
