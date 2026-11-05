(() => {
    document.querySelectorAll('.top-search').forEach(form => {
        const input = form.querySelector('input[type="search"]');
        const clear = form.querySelector('.top-search-clear');
        if (!input || !clear) return;
        input.addEventListener('input', () => { clear.hidden = input.value === ''; });
        clear.addEventListener('click', () => { input.value = ''; form.requestSubmit(); });
        input.addEventListener('search', () => { if (!input.value) form.requestSubmit(); });
    });
})();
