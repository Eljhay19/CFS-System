(() => {
    const dialog = document.querySelector('[data-home-terms-dialog]');
    if (!dialog || dialog.open) return;
    const close = () => window.location.assign(dialog.dataset.homeUrl);
    dialog.addEventListener('cancel', (event) => {
        event.preventDefault();
        close();
    });
    dialog.addEventListener('click', (event) => {
        if (event.target !== dialog) return;
        const box = dialog.getBoundingClientRect();
        if (event.clientX < box.left || event.clientX > box.right || event.clientY < box.top || event.clientY > box.bottom) close();
    });
    dialog.showModal();
    dialog.querySelector('[data-terms-close]')?.focus({ preventScroll: true });
})();
