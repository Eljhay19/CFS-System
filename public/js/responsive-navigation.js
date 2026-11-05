(() => {
    if (document.body.dataset.navigationReady) return;
    document.body.dataset.navigationReady = '1';
    const narrow = matchMedia('(max-width: 1050px)');
    const header = document.querySelector('.topbar');
    const siteToggle = document.querySelector('[data-site-menu-toggle]');
    const adminToggle = document.querySelector('[data-admin-menu-toggle]');
    const sidebar = document.querySelector('.admin-sidebar');
    const setSiteOpen = (open) => {
        header.classList.toggle('site-menu-open', open);
        siteToggle.setAttribute('aria-expanded', String(open));
        siteToggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
    };
    const setAdminOpen = (open) => {
        sidebar?.classList.toggle('admin-menu-open', open);
        adminToggle?.setAttribute('aria-expanded', String(open));
    };
    siteToggle?.addEventListener('click', () => {
        const open = siteToggle.getAttribute('aria-expanded') !== 'true';
        setSiteOpen(open);
        if (open) {
            setAdminOpen(false);
            window.dispatchEvent(new Event('cfs:close-notifications'));
        }
    });
    adminToggle?.addEventListener('click', () => {
        setAdminOpen(adminToggle.getAttribute('aria-expanded') !== 'true');
        setSiteOpen(false);
    });
    window.addEventListener('cfs:notifications-opened', () => setSiteOpen(false));
    document.addEventListener('click', (event) => {
        if (!header.contains(event.target)) setSiteOpen(false);
        if (sidebar && !sidebar.contains(event.target)) setAdminOpen(false);
    });
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        if (header.classList.contains('site-menu-open')) { setSiteOpen(false); siteToggle.focus(); }
        if (sidebar?.classList.contains('admin-menu-open')) { setAdminOpen(false); adminToggle.focus(); }
    });
    narrow.addEventListener('change', () => { setSiteOpen(false); setAdminOpen(false); });
})();
