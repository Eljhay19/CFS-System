(() => {
    const script = document.currentScript;
    const prefix = 'cfs-survey:';
    try {
        if (!script.dataset.surveyToken) {
            if (script.dataset.surveyReset === '1') {
                Object.keys(sessionStorage).filter((key) => key.startsWith(prefix))
                    .forEach((key) => sessionStorage.removeItem(key));
            }
            return;
        }

        const form = document.querySelector('.survey-form');
        if (!form || form.dataset.draftReady) return;
        form.dataset.draftReady = '1';
        const key = prefix + script.dataset.surveyToken + ':' + location.pathname;
        const fields = Array.from(form.elements).filter((field) => field.name && field.name !== '_token');
        const draft = JSON.parse(sessionStorage.getItem(key) || 'null');

        if (draft && form.dataset.serverInput !== '1') {
            fields.forEach((field) => {
                const saved = draft[field.name];
                if (!saved) return;
                if (field.type === 'radio' || field.type === 'checkbox') {
                    field.checked = saved.includes(field.value);
                } else {
                    field.value = saved[0] ?? '';
                }
            });
            const na = form.querySelector('[data-staff-na]');
            const staff = form.querySelector('#staff');
            if (na && staff) staff.disabled = na.checked;
            fields.forEach((field) => {
                if (field.type === 'range') {
                    field.dispatchEvent(new Event('survey:restore'));
                } else if (field.type === 'radio' || (field.type === 'checkbox' && field !== na)) {
                    field.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        }

        const save = () => {
            const values = {};
            fields.forEach((field) => {
                if (field.disabled && field.name.startsWith('follow_up_')) return;
                if (!values[field.name]) values[field.name] = [];
                if (!['radio', 'checkbox'].includes(field.type) || field.checked) {
                    values[field.name].push(field.value);
                }
            });
            try { sessionStorage.setItem(key, JSON.stringify(values)); } catch (error) { /* Storage may be disabled. */ }
        };
        form.addEventListener('input', save);
        form.addEventListener('change', save);
        window.addEventListener('pagehide', save);
    } catch (error) {
        // Server-side session retention still works when browser storage is unavailable.
    }
})();
