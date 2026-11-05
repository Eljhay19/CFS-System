(() => {
    const actionForm = document.querySelector('[data-admin-action-form]');
    if (!actionForm) return;
    const status = actionForm.querySelector('[name="status"]');
    const notes = actionForm.querySelector('[name="action_taken"]');
    const required = actionForm.querySelector('[data-resolution-required]');
    const customerMessage = actionForm.querySelector('[name="customer_message"]');
    const customerMessageRequired = actionForm.querySelector('[data-customer-message-required]');
    const customerMessageOptional = actionForm.querySelector('[data-customer-message-optional]');
    const updateRequired = () => {
        notes.required = status.value === 'Resolved';
        required.hidden = !notes.required;
        if (customerMessage) {
            customerMessage.required = status.value === 'Resolved';
            customerMessageRequired.hidden = !customerMessage.required;
            customerMessageOptional.hidden = customerMessage.required;
        }
    };
    status.addEventListener('change', updateRequired);
    updateRequired();
    document.querySelectorAll('[data-detail-form]').forEach(form => {
        const button = form.querySelector('button[type="submit"]');
        const original = button.innerHTML;
        form.addEventListener('submit', event => {
            if (event.defaultPrevented) return;
            if (form.dataset.saving) { event.preventDefault(); return; }
            form.dataset.saving = '1';
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            const pendingLabel = form === actionForm ? 'Saving…' : 'Sending…';
            const labelNode = [...button.childNodes].find(node => node.nodeType === Node.TEXT_NODE && node.textContent.trim());
            if (labelNode) labelNode.textContent = `${pendingLabel} `;
            else button.prepend(document.createTextNode(pendingLabel));
        });
        window.addEventListener('pageshow', () => {
            delete form.dataset.saving;
            button.disabled = false;
            button.removeAttribute('aria-busy');
            button.innerHTML = original;
        });
    });
})();
