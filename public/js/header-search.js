(() => {
    document.querySelectorAll('.top-search').forEach(form => {
        const input = form.querySelector('input[type="search"]');
        const clear = form.querySelector('.top-search-clear');
        if (!input || !clear) return;

        input.addEventListener('input', () => { clear.hidden = input.value === ''; });
        clear.addEventListener('click', () => { input.value = ''; form.requestSubmit(); });
        input.addEventListener('search', () => { if (!input.value) form.requestSubmit(); });

        if (form.getAttribute('aria-label') !== 'Search questions' || !form.dataset.suggestionsUrl) return;

        const menu = document.createElement('div');
        menu.className = 'top-search-suggestions';
        menu.id = `question-search-suggestions-${Math.random().toString(36).slice(2, 9)}`;
        menu.setAttribute('role', 'listbox');
        menu.setAttribute('aria-label', 'Question search suggestions');
        menu.hidden = true;
        form.append(menu);
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-controls', menu.id);
        input.setAttribute('aria-expanded', 'false');

        let timer = 0;
        let controller = null;
        let activeIndex = -1;

        const closeMenu = () => {
            menu.hidden = true;
            input.setAttribute('aria-expanded', 'false');
            input.removeAttribute('aria-activedescendant');
            activeIndex = -1;
        };

        const showMessage = message => {
            const item = document.createElement('div');
            item.className = 'top-search-suggestion-empty';
            item.textContent = message;
            menu.replaceChildren(item);
            menu.hidden = false;
            input.setAttribute('aria-expanded', 'true');
        };

        const selectSuggestion = question => {
            input.value = question;
            clear.hidden = false;
            closeMenu();
            form.requestSubmit();
        };

        const setActiveOption = nextIndex => {
            const options = Array.from(menu.querySelectorAll('[role="option"]'));
            if (!options.length) return false;
            activeIndex = (nextIndex + options.length) % options.length;
            options.forEach((option, index) => option.setAttribute('aria-selected', String(index === activeIndex)));
            input.setAttribute('aria-activedescendant', options[activeIndex].id);
            options[activeIndex].scrollIntoView({ block: 'nearest' });
            return true;
        };

        const loadSuggestions = async term => {
            controller?.abort();
            controller = new AbortController();
            showMessage('Searching questions…');

            try {
                const url = new URL(form.dataset.suggestionsUrl, window.location.href);
                new FormData(form).forEach((value, key) => {
                    if (key !== 'search' && typeof value === 'string') url.searchParams.set(key, value);
                });
                url.searchParams.set('search', term);
                const response = await fetch(url, {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    signal: controller.signal,
                });
                if (!response.ok) throw new Error('Question suggestions could not be loaded.');
                const payload = await response.json();
                const matches = (payload.suggestions || []).filter(item => item?.question).slice(0, 8);

                if (input.value.trim() !== term) return;
                document.dispatchEvent(new CustomEvent('questions:live-search', {
                    detail: { term, suggestions: payload.suggestions || [] },
                }));
                if (!matches.length) {
                    showMessage('No matching questions.');
                    return;
                }

                const options = matches.map((match, index) => {
                    const option = document.createElement('button');
                    option.className = 'top-search-suggestion';
                    option.type = 'button';
                    option.id = `${menu.id}-option-${index}`;
                    option.setAttribute('role', 'option');
                    option.setAttribute('aria-selected', 'false');
                    option.dataset.question = match.question;
                    const title = document.createElement('strong');
                    title.textContent = match.question;
                    option.append(title);
                    const details = [match.section, match.type].filter(Boolean).join(' · ');
                    if (details) {
                        const note = document.createElement('small');
                        note.textContent = details;
                        option.append(note);
                    }
                    return option;
                });
                menu.replaceChildren(...options);
                menu.hidden = false;
                input.setAttribute('aria-expanded', 'true');
                activeIndex = -1;
            } catch (error) {
                if (error.name !== 'AbortError' && input.value.trim() === term) {
                    showMessage('Suggestions unavailable. Submit to search.');
                }
            }
        };

        input.addEventListener('input', () => {
            window.clearTimeout(timer);
            controller?.abort();
            const term = input.value.trim();
            if (term.length < 1) {
                document.dispatchEvent(new CustomEvent('questions:live-search', {
                    detail: { term: '', suggestions: null },
                }));
                closeMenu();
                return;
            }
            timer = window.setTimeout(() => loadSuggestions(term), 220);
        });

        input.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                closeMenu();
                return;
            }
            if (event.key === 'ArrowDown' && !menu.hidden) {
                event.preventDefault();
                setActiveOption(activeIndex + 1);
            } else if (event.key === 'ArrowUp' && !menu.hidden) {
                event.preventDefault();
                setActiveOption(activeIndex < 0 ? 0 : activeIndex - 1);
            } else if (event.key === 'Enter' && !menu.hidden && activeIndex >= 0) {
                event.preventDefault();
                const option = menu.querySelectorAll('[role="option"]')[activeIndex];
                if (option) selectSuggestion(option.dataset.question);
            }
        });

        menu.addEventListener('click', event => {
            const option = event.target.closest('[role="option"]');
            if (option) selectSuggestion(option.dataset.question);
        });
        menu.addEventListener('pointerdown', event => {
            if (event.target.closest('[role="option"]')) event.preventDefault();
        });
        document.addEventListener('click', event => {
            if (!form.contains(event.target)) closeMenu();
        });
    });
})();
