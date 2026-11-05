(() => {
    const otpInputs = [...document.querySelectorAll('[data-otp-digit]')];
    const verificationCode = document.getElementById('verification-code');
    if (otpInputs.length && verificationCode) {
        const syncCode = () => { verificationCode.value = otpInputs.map(input => input.value).join(''); };
        const fillFrom = (start, value) => {
            const digits = value.replace(/\D/g, '').slice(0, otpInputs.length - start);
            [...digits].forEach((digit, index) => { otpInputs[start + index].value = digit; });
            syncCode();
            otpInputs[Math.min(start + digits.length, otpInputs.length - 1)]?.focus();
        };
        otpInputs.forEach((input, index) => {
            input.addEventListener('input', () => {
                const digits = input.value.replace(/\D/g, '');
                if (digits.length > 1) {
                    fillFrom(index, digits);
                    return;
                }
                const digit = digits.slice(-1);
                input.value = digit;
                syncCode();
                if (digit && index < otpInputs.length - 1) otpInputs[index + 1].focus();
            });
            input.addEventListener('keydown', event => {
                if (event.key === 'Backspace' && !input.value && index > 0) otpInputs[index - 1].focus();
                if (event.key === 'ArrowLeft' && index > 0) { event.preventDefault(); otpInputs[index - 1].focus(); }
                if (event.key === 'ArrowRight' && index < otpInputs.length - 1) { event.preventDefault(); otpInputs[index + 1].focus(); }
            });
            input.addEventListener('paste', event => {
                event.preventDefault();
                fillFrom(index, event.clipboardData?.getData('text') || '');
            });
        });
        document.querySelector('.recovery-verify-form')?.addEventListener('submit', event => {
            syncCode();
            if (verificationCode.value.length !== otpInputs.length) {
                event.preventDefault();
                otpInputs.find(input => !input.value)?.focus();
                otpInputs[0].setCustomValidity('Enter all six digits of the verification code.');
                otpInputs[0].reportValidity();
                return;
            }
            otpInputs[0].setCustomValidity('');
        });
        otpInputs.forEach(input => input.addEventListener('input', () => otpInputs[0].setCustomValidity('')));
    }

    document.querySelectorAll('[data-password-toggle]').forEach(toggle => {
        toggle.addEventListener('click', () => {
            const input = document.getElementById(toggle.getAttribute('aria-controls'));
            const visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            toggle.setAttribute('aria-pressed', String(visible));
            const label = `${visible ? 'Hide' : 'Show'} ${input.labels[0].textContent.toLowerCase()}`;
            toggle.setAttribute('aria-label', label);
            toggle.title = label;
        });
    });
    document.querySelectorAll('[data-password-form]').forEach(form => {
    if (!form || form.dataset.ready) return;
    form.dataset.ready = '1';
    const button = form.querySelector('[data-primary-submit]') || form.querySelector('button[type="submit"]');
    const buttons = [...form.querySelectorAll('button[type="submit"]')];
    const label = button.textContent;
    const width = button.style.width;
    const startedAt = Date.now();
    const cooldown = () => Math.max(0, Number(form.dataset.resendAt || 0) - Number(form.dataset.serverTime || 0) - Math.floor((Date.now() - startedAt) / 1000));
    const updateCooldown = () => {
        if (!form.hasAttribute('data-resend-at')) return;
        const seconds = cooldown();
        buttons.forEach(control => { control.disabled = form.getAttribute('aria-busy') === 'true' || seconds > 0; });
        const countdown = form.querySelector('.recovery-cooldown');
        if (countdown) {
            const time = `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
            countdown.textContent = seconds > 0 ? `(${time})` : '';
        }
    };
    updateCooldown();
    if (form.hasAttribute('data-resend-at')) window.setInterval(updateCooldown, 1000);
    form.addEventListener('submit', event => {
        if (form.getAttribute('aria-busy') === 'true' || button.disabled || !form.checkValidity()) { event.preventDefault(); return; }
        button.style.width = `${button.getBoundingClientRect().width}px`;
        buttons.forEach(control => { control.disabled = true; });
        button.textContent = form.dataset.pendingLabel;
        form.setAttribute('aria-busy', 'true');
    });
    window.addEventListener('pageshow', () => {
        buttons.forEach(control => { control.disabled = false; });
        button.textContent = label;
        button.style.width = width;
        form.removeAttribute('aria-busy');
        updateCooldown();
    });
    });
})();
