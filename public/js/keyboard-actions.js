(() => {
    if (window.cfsKeyboardActions) return;
    window.cfsKeyboardActions = true;
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' || event.defaultPrevented || event.isComposing || event.repeat || event.altKey) return;
        const field = event.target;
        if (!(field instanceof HTMLElement) || field.closest('[contenteditable="true"]')) return;
        if (field.matches('input[type="checkbox"], input[type="radio"]') && !field.disabled) {
            event.preventDefault();
            field.click();
            return;
        }
        const multiline = field.matches('textarea');
        if (multiline && !event.ctrlKey && !event.metaKey) return;
        if (!multiline && !field.matches('input:not([type="button"]):not([type="submit"]):not([type="reset"]):not([type="file"]):not([type="hidden"])')) return;
        const form = field.form;
        if (!form || field.disabled || field.readOnly) return;
        const submit = form.querySelector('[data-primary-submit]') || form.querySelector('button[type="submit"], input[type="submit"], button:not([type])');
        if (!submit || submit.disabled) return;
        event.preventDefault();
        form.requestSubmit(submit);
    });
})();
