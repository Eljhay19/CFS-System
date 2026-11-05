(() => {
    const toast = document.querySelector('[data-flash-toast]');
    if (!toast) return;

    // Keep the notification centered in the viewport, outside the animated
    // page container, so it never shifts the content or gets clipped.
    document.body.appendChild(toast);
    toast.classList.add('flash-toast');
    window.setTimeout(() => {
        toast.classList.add('is-leaving');
        window.setTimeout(() => toast.remove(), 160);
    }, 1450);
})();
