(() => {
    const form = document.querySelector('.hero-form');
    if (!form || form.dataset.consentReady) return;
    form.dataset.consentReady = '1';
    const agree = form.querySelector('[name="consent"][value="agree"]');
    const button = form.querySelector('[data-start-survey]');
    let termsConfirmed = form.dataset.termsConfirmed === '1';
    let interacted = false;

    const restoreConsent = () => {
        form.querySelectorAll('[name="consent"]').forEach((input) => { input.checked = false; });
        agree.checked = termsConfirmed;
        button.disabled = !termsConfirmed;
    };
    const openTerms = () => {
        termsConfirmed = false;
        restoreConsent();
        window.location.assign(form.dataset.termsUrl);
    };
    const update = () => {
        if (agree.checked && !termsConfirmed) { openTerms(); return; }
        button.disabled = !termsConfirmed || !agree.checked;
    };

    form.addEventListener('click', (event) => {
        if (!event.target.closest('[data-home-agree-option]')) return;
        if (!termsConfirmed || event.target.closest('a')) {
            event.preventDefault();
            openTerms();
        }
    }, true);
    agree.addEventListener('keydown', (event) => {
        if (!termsConfirmed && [' ', 'Enter'].includes(event.key)) {
            event.preventDefault();
            openTerms();
        }
    });
    form.querySelectorAll('[name="consent"]').forEach((input) => input.addEventListener('change', () => {
        interacted = true;
        update();
    }));
    form.addEventListener('submit', (event) => {
        if (!termsConfirmed || !agree.checked) {
            event.preventDefault();
            if (!termsConfirmed) openTerms();
            else update();
        }
    });
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) termsConfirmed = false;
        if (event.persisted || !interacted) restoreConsent();
    });
    restoreConsent();
})();
