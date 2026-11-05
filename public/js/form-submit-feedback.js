(() => {
    const loadingLabel = (button) => {
        const value = button instanceof HTMLInputElement ? button.value : button.textContent;
        const text = (value || '').trim().replace(/\s+/g, ' ');
        if (/send|email|reply/i.test(text)) return 'Sending…';
        if (/submit feedback|retry submission/i.test(text)) return 'Submitting…';
        if (/save|update/i.test(text)) return 'Saving…';
        if (/verify/i.test(text)) return 'Verifying…';
        if (/login|sign in/i.test(text)) return 'Signing in…';
        if (/confirm agreement|confirm/i.test(text)) return 'Confirming…';
        if (/review answers/i.test(text)) return 'Reviewing…';
        if (/\bnext\b|continue|start survey/i.test(text)) return 'Continuing…';
        if (/apply|search/i.test(text)) return 'Applying…';
        if (/archive/i.test(text)) return 'Archiving…';
        if (/restore/i.test(text)) return 'Restoring…';
        if (/publish/i.test(text)) return 'Publishing…';
        if (/delete|remove/i.test(text)) return 'Removing…';
        if (/start over|reset/i.test(text)) return 'Resetting…';
        return null;
    };

    document.addEventListener('submit', event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.dataset.submitting === '1') return;
        // These workflows own their own validation and pending state. Running a
        // second handler here can overwrite their label or remove button icons.
        if (form.matches('[data-contact-form], .survey-form, [data-password-form], [data-detail-form], [data-admin-action-form]')) return;

        const submitter = event.submitter || form.querySelector('button[type="submit"], input[type="submit"]');
        if (!submitter) return;

        const label = submitter.dataset.submittingLabel || loadingLabel(submitter);
        // Leave navigation and other actions with their original wording.
        if (!label) return;

        form.dataset.submitting = '1';
        form.setAttribute('aria-busy', 'true');
        if (!(submitter instanceof HTMLInputElement)) submitter.dataset.originalSubmitHtml = submitter.innerHTML;
        submitter.dataset.originalSubmitAriaLabel = submitter.getAttribute('aria-label') || '';
        if (submitter instanceof HTMLInputElement) {
            submitter.dataset.originalSubmitLabel = submitter.value;
            submitter.setAttribute('aria-label', label);
        }
        else submitter.textContent = label;
    }, true);

    window.addEventListener('pageshow', () => {
        document.querySelectorAll('form[aria-busy="true"]').forEach(form => {
            form.removeAttribute('aria-busy');
            delete form.dataset.submitting;
            const submitter = form.querySelector('[data-original-submit-label], [data-original-submit-html]');
            if (!submitter) return;
            if (submitter instanceof HTMLInputElement) submitter.value = submitter.dataset.originalSubmitLabel;
            else submitter.innerHTML = submitter.dataset.originalSubmitHtml;
            const previousAriaLabel = submitter.dataset.originalSubmitAriaLabel;
            if (previousAriaLabel) submitter.setAttribute('aria-label', previousAriaLabel);
            else submitter.removeAttribute('aria-label');
            delete submitter.dataset.originalSubmitLabel;
            delete submitter.dataset.originalSubmitHtml;
            delete submitter.dataset.originalSubmitAriaLabel;
        });
    });
})();
