(() => {
    document.querySelectorAll('[data-editable-form]').forEach(form => {
        const controls = [...form.querySelectorAll('input:not([type="hidden"]), textarea, select')];
        const editActions = form.querySelector('[data-edit-actions]') || form.querySelector('[data-edit-start]')?.parentElement;
        const saveActions = form.querySelector('[data-save-actions]');
        const setEditing = editing => {
            form.dataset.editing = editing ? 'true' : 'false';
            controls.forEach(control => {
                if (control.readOnly) return;
                control.disabled = !editing;
                if (control.name === 'current_password') control.required = editing && Boolean(newPassword?.value);
            });
            if (editActions) editActions.hidden = editing;
            if (saveActions) saveActions.hidden = !editing;
        };
        const resetValues = () => controls.forEach(control => {
            if (control.type === 'checkbox' || control.type === 'radio') control.checked = control.defaultChecked;
            else if (control.tagName === 'SELECT') [...control.options].forEach(option => { option.selected = option.defaultSelected; });
            else if (control.type !== 'password') control.value = control.defaultValue;
            if (control.type === 'password') control.value = '';
        });
        const newPassword = form.querySelector('[name="password"]');
        const currentPassword = form.querySelector('[name="current_password"]');
        const updatePasswordRequirement = () => {
            if (currentPassword) currentPassword.required = form.dataset.editing === 'true' && Boolean(newPassword?.value);
        };
        newPassword?.addEventListener('input', updatePasswordRequirement);
        setEditing(false);
        form.querySelector('[data-edit-start]')?.addEventListener('click', () => setEditing(true));
        form.querySelector('[data-edit-cancel]')?.addEventListener('click', () => {
            resetValues();
            const passwordDetails = form.querySelector('.settings-password-options');
            if (passwordDetails) passwordDetails.open = false;
            updatePasswordRequirement();
            setEditing(false);
        });
        form.addEventListener('submit', event => {
            if (form.dataset.editing !== 'true') event.preventDefault();
        });
    });
    document.querySelectorAll('[data-password-toggle]').forEach(button => button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.passwordToggle);
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.setAttribute('aria-label', (visible ? 'Hide ' : 'Show ') + input.labels[0].textContent.toLowerCase());
        button.setAttribute('aria-pressed', visible ? 'true' : 'false');
        button.querySelector('.ui-icon').style.setProperty('--icon-url', `url("${visible ? button.dataset.iconHide : button.dataset.iconShow}")`);
    }));
    const notificationEmail = document.getElementById('notification_email');
    const accountEmailPreference = document.getElementById('use_account_email_for_feedback');
    if (notificationEmail && accountEmailPreference) {
        const accountEmail = (notificationEmail.dataset.accountEmail || '').trim().toLowerCase();
        const syncNotificationAddress = () => {
            const isAccountAddress = notificationEmail.value.trim().toLowerCase() === accountEmail;
            if (!isAccountAddress) accountEmailPreference.checked = false;
            notificationEmail.required = !accountEmailPreference.checked;
        };
        notificationEmail.addEventListener('input', syncNotificationAddress);
        accountEmailPreference.addEventListener('change', () => {
            if (accountEmailPreference.checked) notificationEmail.value = notificationEmail.dataset.accountEmail || '';
            syncNotificationAddress();
        });
        syncNotificationAddress();
    }
    const link = document.getElementById('survey_link');
    if (!link) return;
    document.querySelectorAll('[data-copy-survey]').forEach(button => button.addEventListener('click', async () => {
        const status = document.querySelector('[data-copy-status]');
        try {
            if (navigator.clipboard?.writeText) await navigator.clipboard.writeText(link.value);
            else {
                link.select();
                if (!document.execCommand('copy')) throw new Error('Copy unavailable');
                button.focus();
            }
            status.textContent = 'Link copied.';
        } catch (error) { status.textContent = 'The link could not be copied.'; }
    }));
    const canvas = document.querySelector('[data-survey-qr]');
    if (canvas) {
      try {
        if (typeof qrcode !== 'function') throw new Error('QR generator unavailable');
        const code = qrcode(0, 'M');
        code.addData(canvas.dataset.url);
        code.make();
        const count = code.getModuleCount();
        const unit = 5;
        canvas.width = canvas.height = (count + 8) * unit;
        const context = canvas.getContext('2d');
        context.fillStyle = '#fff';
        context.fillRect(0, 0, canvas.width, canvas.height);
        context.fillStyle = '#000';
        for (let row = 0; row < count; row++) for (let column = 0; column < count; column++) {
            if (code.isDark(row, column)) context.fillRect((column + 4) * unit, (row + 4) * unit, unit, unit);
        }
        const download = document.querySelector('[data-download-qr]');
        download.setAttribute('aria-disabled', 'true');
        download.addEventListener('click', event => {
            if (download.getAttribute('aria-disabled') === 'true') event.preventDefault();
        });
        canvas.toBlob(blob => {
            if (!blob) {
                document.querySelector('[data-copy-status]').textContent = 'QR download is unavailable. Please try again.';
                return;
            }
            const url = URL.createObjectURL(blob);
            download.href = url;
            download.dataset.qrReady = '1';
            download.removeAttribute('aria-disabled');
            // The document owns this URL, including pending downloads and Back/Forward restores.
        }, 'image/png');
      } catch (error) {
        canvas.hidden = true;
        canvas.parentElement.classList.add('qr-unavailable');
        document.querySelector('[data-download-qr]').setAttribute('aria-disabled', 'true');
        document.querySelector('[data-copy-status]').textContent = 'QR code is unavailable.';
      }
    }
    const preview = document.querySelector('[data-survey-preview]');
    document.querySelector('[data-preview-survey]')?.addEventListener('click', () => {
        const open = document.getElementById('survey_enabled').checked;
        preview.querySelector('[data-preview-message]').textContent = open ? 'Accepting responses' : (document.getElementById('closed_survey_message').value || 'Thanks for visiting. Our survey is currently closed.');
        preview.querySelector('[data-preview-open]').hidden = !open;
        preview.showModal();
    });
    document.querySelector('[data-preview-close]')?.addEventListener('click', () => preview.close());
})();
