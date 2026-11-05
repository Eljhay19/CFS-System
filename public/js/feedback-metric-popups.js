(() => {
    // Delegate from document because the live dashboard refresh replaces the
    // KPI cards and dialogs without reloading this script.
    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-feedback-popup]');
        if (trigger) {
            const dialog = document.getElementById(`feedback-popup-${trigger.dataset.feedbackPopup}`);
            if (dialog && !dialog.open) dialog.showModal();
            return;
        }

        const close = event.target.closest('[data-feedback-popup-close]');
        if (close) {
            close.closest('dialog')?.close();
            return;
        }

        if (event.target.matches('dialog.feedback-record-dialog')) {
            event.target.close();
        }
    });
})();
