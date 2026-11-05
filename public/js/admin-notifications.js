(() => {
    const menu = document.querySelector('[data-notification-menu]');
    if (!menu || menu.dataset.ready) return;
    menu.dataset.ready = '1';
    const lifecycle = window.cfsPageLifecycle;
    const trigger = menu.querySelector('[data-notification-trigger]');
    const popover = menu.querySelector('[data-notification-popover]');
    const list = menu.querySelector('[data-notification-list]');
    const badge = menu.querySelector('[data-notification-badge]');
    const count = menu.querySelector('[data-notification-count]');
    const readAll = menu.querySelector('[data-notification-read-all]');
    const close = menu.querySelector('[data-notification-close]');
    let loading = false;
    let markingRead = false;
    let rendered = null;

    const positionPopover = () => {
        const bounds = trigger.getBoundingClientRect();
        const width = Math.min(304, innerWidth - 24);
        const left = Math.max(12, Math.min(innerWidth - width - 12, bounds.right - width + 10));
        popover.style.setProperty('--notification-left', `${left}px`);
        popover.style.setProperty('--notification-top', `${Math.max(12, Math.min(bounds.bottom + 8, innerHeight - 160))}px`);
        popover.style.setProperty('--notification-arrow', `${Math.max(14, Math.min(width - 24, bounds.x + bounds.width / 2 - left - 5))}px`);
    };
    const setOpen = (open) => {
        if (open) {
            window.dispatchEvent(new Event('cfs:notifications-opened'));
            document.body.append(popover);
            positionPopover();
        }
        popover.hidden = !open;
        trigger.setAttribute('aria-expanded', String(open));
    };
    const setCount = (value) => {
        const total = Number(value || 0);
        const text = total > 99 ? '99+' : String(total);
        badge.textContent = count.textContent = text;
        badge.classList.toggle('is-hidden', total < 1);
        count.hidden = total < 1;
        count.style.display = total < 1 ? 'none' : 'inline-flex';
        readAll.disabled = total < 1 || markingRead;
        trigger.setAttribute('aria-label', `Open notifications, ${total} unread`);
    };
    const icons = new Set(['triangle-alert', 'clipboard-list', 'message-square', 'refresh-cw', 'monitor']);
    const render = (payload) => {
        setCount(payload.unread_count);
        const key = JSON.stringify(payload.notifications || []);
        if (key === rendered) return;
        rendered = key;
        const scrollTop = list.scrollTop;
        const fragment = document.createDocumentFragment();
        for (const item of payload.notifications || []) {
            const row = document.createElement('a');
            row.className = `notification-popup-row${item.is_read ? '' : ' unread'}`;
            row.href = item.url;
            row.dataset.notificationId = item.id;
            const icon = document.createElement('span');
            icon.className = 'notification-icon-wrap';
            const symbol = document.createElement('span');
            symbol.className = 'ui-icon notification-popup-icon';
            symbol.setAttribute('aria-hidden', 'true');
            const name = icons.has(item.icon) ? item.icon : 'message-square';
            symbol.style.setProperty('--icon-url', `url("${menu.dataset.notificationIcons}/${name}.svg")`);
            icon.append(symbol);
            const copy = document.createElement('span');
            copy.className = 'notification-popup-copy';
            const title = document.createElement('strong');
            title.textContent = item.title;
            const message = document.createElement('p');
            message.textContent = item.detail || item.message;
            copy.append(title, message);
            const time = document.createElement('time');
            time.className = 'notification-popup-time';
            time.textContent = item.time;
            if (item.created_at) time.dateTime = item.created_at;
            time.title = item.submitted_at || item.created_at || '';
            const meta = document.createElement('span');
            meta.className = 'notification-popup-meta';
            if (!item.is_read) {
                const dot = document.createElement('span');
                dot.className = 'notification-unread-dot';
                dot.setAttribute('aria-label', 'Unread');
                meta.append(dot);
            }
            meta.append(time);
            row.append(icon, copy, meta);
            fragment.append(row);
        }
        if (!fragment.childNodes.length) {
            const empty = document.createElement('p');
            empty.className = 'notification-empty';
            empty.textContent = 'No notifications yet.';
            fragment.append(empty);
        }
        list.replaceChildren(fragment);
        list.scrollTop = scrollTop;
    };
    const request = async (url, options = {}) => {
        const controller = lifecycle?.controller() || new AbortController();
        const timeout = setTimeout(() => controller.abort(), 10000);
        try {
            const response = await fetch(url, { ...options, signal: controller.signal, cache: 'no-store',
                headers: { Accept: 'application/json', ...options.headers } });
            if (!response.ok) throw new Error('Notification request failed');
            return await response.json();
        } finally { clearTimeout(timeout); lifecycle?.release(controller); }
    };
    const load = async () => {
        if (loading || markingRead || document.hidden || lifecycle?.suspended) return;
        loading = true;
        try {
            const payload = await request(menu.dataset.notificationUrl);
            if (!markingRead && !lifecycle?.suspended) render(payload);
        } catch (error) {
            if (error.name !== 'AbortError' && !lifecycle?.suspended && rendered === null) list.innerHTML = '<p class="notification-empty">Unable to load notifications. Please try again.</p>';
        } finally { loading = false; }
    };
    const markAllAsRead = async () => {
        if (markingRead || Number(badge.textContent) < 1) return;
        markingRead = true;
        readAll.disabled = true;
        try {
            const payload = await request(menu.dataset.notificationReadUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': menu.dataset.notificationCsrf } });
            rendered = null;
            render(payload);
            window.dispatchEvent(new Event('cfs:notifications-updated'));
        } catch (error) {
            readAll.title = 'Unable to update notifications. Please try again.';
        } finally {
            markingRead = false;
            readAll.disabled = Number(badge.textContent) < 1;
        }
    };
    trigger.addEventListener('click', () => {
        setOpen(popover.hidden);
        if (!popover.hidden) load();
    });
    close?.addEventListener('click', () => { setOpen(false); trigger.focus(); });
    window.addEventListener('cfs:close-notifications', () => setOpen(false));
    readAll.addEventListener('click', async () => {
        await markAllAsRead();
    });
    document.addEventListener('click', (event) => { if (!menu.contains(event.target) && !popover.contains(event.target)) setOpen(false); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !popover.hidden) { setOpen(false); trigger.focus(); } });
    document.addEventListener('visibilitychange', () => { if (!document.hidden) load(); });
    window.addEventListener('focus', load);
    window.addEventListener('cfs:page-resumed', load);
    window.addEventListener('resize', () => { if (!popover.hidden) positionPopover(); });
    window.addEventListener('scroll', () => { if (!popover.hidden) positionPopover(); }, { passive: true });
    load();
    setInterval(load, 3000);
})();
