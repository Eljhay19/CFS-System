(() => {
    const dialog = document.querySelector('[data-step-dialog]');
    if (!dialog) return;
    const form = dialog.querySelector('form');
    const steps = JSON.parse(document.getElementById('survey-steps-data').textContent);
    const open = (id, retain = false) => {
        const step = steps[id];
        form.action = step?.action || form.dataset.createUrl;
        form.querySelector('[data-step-method]').disabled = !step;
        form.querySelector('[data-step-method]').value = step ? 'PUT' : '';
        form.querySelector('[data-step-mode]').value = step ? 'edit' : 'add';
        form.querySelector('[data-step-id]').value = step?.id || '';
        dialog.querySelector('[data-step-heading]').textContent = step ? 'Edit Survey Step' : 'Add Survey Step';
        if (!retain) {
            form.querySelector('#step-title').value = step?.title || '';
            form.querySelector('#step-description').value = step?.description || '';
        }
        dialog.showModal();
        form.querySelector('#step-title').focus();
    };
    document.querySelector('[data-step-add]').addEventListener('click', () => open(null));
    document.querySelectorAll('[data-step-edit]').forEach(button => button.addEventListener('click', () => open(button.dataset.stepEdit)));
    dialog.querySelectorAll('[data-step-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
    if (dialog.dataset.errorMode) open(dialog.dataset.errorId, true);
})();
