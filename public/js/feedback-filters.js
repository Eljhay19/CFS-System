(() => {
    if (window.cfsDateFilters) return;
    window.cfsDateFilters = true;
    const initial = new WeakMap();
    const values = form => JSON.stringify([...new FormData(form)]);
    const initialize = () => document.querySelectorAll('[data-date-ranges]').forEach(form => {
        initial.set(form, values(form));
        form.dataset.filtersDirty = '0';
    });
    initialize();
    window.addEventListener('cfs:panel-updated', initialize);
    const markDirty = form => { form.dataset.filtersDirty = String(values(form) !== initial.get(form) ? 1 : 0); };
    document.addEventListener('input', event => {
        const form = event.target.closest('[data-date-ranges]');
        if (!form) return;
        if (event.target.matches('[data-date-from], [data-date-to]')) form.querySelector('[data-date-preset]').value = 'custom';
        markDirty(form);
    });
    document.addEventListener('change', (event) => {
        if (event.target.matches('[data-trend-period]')) {
            const filters = document.querySelector('#feedback-filters');
            filters.querySelector('[name="period"]').value = event.target.value;
            filters.requestSubmit();
            return;
        }
        const form = event.target.closest('[data-date-ranges]');
        if (!form) return;
        const from = form.querySelector('[data-date-from]');
        const to = form.querySelector('[data-date-to]');
        const preset = form.querySelector('[data-date-preset]');
        if (event.target === preset) {
            const range = JSON.parse(form.dataset.dateRanges)[preset.value];
            if (range) { from.value = range.from; to.value = range.to; }
        } else if (event.target === from || event.target === to) preset.value = 'custom';
        to.min = from.value;
        markDirty(form);
    });
    document.addEventListener('click', (event) => {
        document.querySelectorAll('[data-export-menu][open]').forEach((menu) => {
            if (!menu.contains(event.target)) menu.open = false;
        });
    });
})();
