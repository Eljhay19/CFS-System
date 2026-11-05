(() => {
    const form = document.querySelector('[data-contact-form]');
    if (!form || form.dataset.ready) return;
    form.dataset.ready = '1';
    const button = form.querySelector('[type="submit"]');
    const label = form.querySelector('[data-contact-submit-label]');
    form.addEventListener('submit', (event) => {
        if (button.disabled) { event.preventDefault(); return; }
        button.disabled = true;
        label.textContent = 'Sending…';
    });
    window.addEventListener('pageshow', () => { button.disabled = false; label.textContent = 'Send Message'; });
})();
