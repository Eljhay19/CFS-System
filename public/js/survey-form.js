(() => {
    const form = document.querySelector('[data-survey-form]');
    if (!form || form.dataset.ready) return;
    form.dataset.ready = '1';
    form.querySelectorAll('[data-range]').forEach((slider) => {
        const shell = slider.closest('[data-range-shell]');
        const marker = shell.querySelector('[data-range-selected]');
        const output = form.querySelector(`[data-output-for="${slider.id}"]`);
        const draw = () => {
            shell.style.setProperty('--range-progress', `${((Number(slider.value) - 1) / 9) * 100}%`);
            shell.classList.toggle('is-selected', marker.value === '1');
            slider.setCustomValidity(slider.dataset.required === '1' && marker.value !== '1' && !slider.disabled ? 'Please choose a rating from 1 to 10.' : '');
        };
        const select = () => {
            if (slider.disabled) return;
            marker.value = '1';
            if (output) output.textContent = slider.value;
            draw();
        };
        slider.addEventListener('input', select);
        slider.addEventListener('pointerup', select);
        slider.addEventListener('survey:restore', () => {
            if (output) output.textContent = slider.disabled ? 'N/A' : (marker.value === '1' ? slider.value : 'Select');
            shell.classList.toggle('is-disabled', slider.disabled);
            draw();
        });
        const na = slider.id === 'staff' ? form.querySelector('[data-staff-na]') : null;
        na?.addEventListener('change', () => {
            slider.disabled = na.checked;
            marker.value = na.checked ? '1' : '';
            shell.classList.toggle('is-disabled', na.checked);
            if (output) output.textContent = na.checked ? 'N/A' : 'Select';
            draw();
        });
        // Optional untouched sliders are omitted rather than recorded as the default 5.
        if (slider.dataset.required !== '1') slider.dataset.optionalRating = '1';
        draw();
    });
    const comment = form.querySelector('#problem_comment');
    const syncComment = () => {
        if (!comment) return;
        comment.required = form.querySelector('[name="problem_today"]:checked')?.value === 'Yes';
        const star = form.querySelector('[data-comment-required]');
        if (star) star.hidden = !comment.required;
    };
    form.querySelectorAll('[name="problem_today"]').forEach((input) => input.addEventListener('change', syncComment));
    syncComment();
    const followUp = form.querySelector('#follow_up_requested');
    const followUpFields = form.querySelector('[data-follow-up-fields]');
    const email = form.querySelector('#follow_up_email');
    const consent = form.querySelector('#follow_up_consent');
    const syncFollowUp = () => {
        if (!followUp) return;
        followUpFields.hidden = !followUp.checked;
        followUp.setAttribute('aria-expanded', String(followUp.checked));
        [email, consent].forEach(input => {
            input.disabled = !followUp.checked;
            input.required = followUp.checked;
        });
        if (!followUp.checked) { email.value = ''; consent.checked = false; }
    };
    followUp?.addEventListener('change', syncFollowUp);
    email?.addEventListener('input', () => { consent.checked = false; });
    form.addEventListener('invalid', event => {
        if (followUpFields?.contains(event.target)) event.target.scrollIntoView({ block: 'nearest' });
    }, true);
    syncFollowUp();
    if (followUpFields?.querySelector('[aria-invalid="true"]')) followUpFields.scrollIntoView({ block: 'nearest' });
    const button = form.querySelector('button[type="submit"]');
    const label = button?.innerHTML;
    form.addEventListener('submit', (event) => {
        syncComment();
        syncFollowUp();
        if (button?.disabled || !form.checkValidity()) {
            event.preventDefault();
            form.reportValidity();
            return;
        }
        form.querySelectorAll('[data-optional-rating]').forEach((slider) => {
            if (slider.closest('[data-range-shell]').querySelector('[data-range-selected]').value !== '1') slider.disabled = true;
        });
        if (button) {
            const currentLabel = button.textContent.trim();
            const pendingLabel = /submit feedback|retry submission/i.test(currentLabel)
                ? 'Submitting…'
                : (/review answers/i.test(currentLabel) ? 'Reviewing…' : 'Continuing…');
            const labelNode = [...button.childNodes].find(node => node.nodeType === Node.TEXT_NODE && node.textContent.trim());
            if (labelNode) labelNode.textContent = `${pendingLabel} `;
            else button.prepend(document.createTextNode(pendingLabel));
            button.disabled = true;
        }
    });
    window.addEventListener('pageshow', () => {
        if (button) { button.disabled = false; button.innerHTML = label; }
        form.querySelectorAll('[data-optional-rating]').forEach((slider) => { slider.disabled = false; });
    });
})();
