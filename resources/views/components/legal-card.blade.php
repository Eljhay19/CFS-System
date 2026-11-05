@props([
    'title',
    'intro',
    'sections',
    'navLabel' => 'Information sections',
    'requiresAgreement' => false,
    'agreementLabel' => null,
    'completeUrl' => null,
    'confirmationToken' => null,
])

<section class="terms-card {{ $requiresAgreement ? '' : 'is-info-only' }}" data-terms-card>
    <button class="terms-close" type="button" aria-label="Close {{ $title }}" data-terms-close>&times;</button>

    <aside class="terms-nav" aria-label="{{ $navLabel }}">
        @foreach ($sections as $index => $section)
            <button class="terms-tab {{ $index === 0 ? 'is-active' : '' }}" type="button" data-terms-tab="{{ $section['id'] }}">
                <svg viewBox="0 0 24 24" aria-hidden="true">{!! $section['icon'] !!}</svg>
                <span>{{ $section['label'] }}</span>
            </button>
        @endforeach
    </aside>

    <div class="terms-content">
        <div class="terms-heading">
            <h1>{{ $title }}</h1>
            <p>{{ $intro }}</p>
        </div>

        <div class="terms-panels">
            @foreach ($sections as $index => $section)
                <article class="terms-panel {{ $index === 0 ? 'is-active' : '' }}" id="terms-{{ $section['id'] }}" data-terms-panel="{{ $section['id'] }}">
                    <h2>{{ $section['title'] }}</h2>
                    @foreach ($section['paragraphs'] as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                </article>
            @endforeach
        </div>

        @if ($requiresAgreement)
            <form class="terms-actions" action="{{ $completeUrl }}" method="POST" data-terms-confirm>
                @csrf
                <input type="hidden" name="terms_token" value="{{ $confirmationToken }}">
                <label class="terms-agree">
                    <input type="checkbox" name="agreement" value="1" required data-terms-agree>
                    <span></span>
                    {{ $agreementLabel ?? 'I agree with the Terms & Conditions' }}
                </label>
                <button class="button terms-next-button" type="button" data-terms-next>
                    Next: {{ $sections[1]['label'] ?? 'Continue' }}
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"></path><path d="m13 6 6 6-6 6"></path></svg>
                </button>
            </form>
        @endif
    </div>
</section>

<script>
    (() => {
        const card = document.querySelector('[data-terms-card]');
        if (!card) {
            return;
        }

        const tabs = Array.from(card.querySelectorAll('[data-terms-tab]'));
        const panels = Array.from(card.querySelectorAll('[data-terms-panel]'));
        const next = card.querySelector('[data-terms-next]');
        const agree = card.querySelector('[data-terms-agree]');
        const close = card.querySelector('[data-terms-close]');
        const form = card.querySelector('[data-terms-confirm]');
        let activeIndex = 0;

        const arrowIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"></path><path d="m13 6 6 6-6 6"></path></svg>';

        const setActive = (index) => {
            activeIndex = Math.max(0, Math.min(index, tabs.length - 1));
            const activeId = tabs[activeIndex].dataset.termsTab;

            tabs.forEach((tab) => {
                tab.classList.toggle('is-active', tab.dataset.termsTab === activeId);
            });
            panels.forEach((panel) => {
                panel.classList.toggle('is-active', panel.dataset.termsPanel === activeId);
            });
            if (next) {
                const nextSection = tabs[activeIndex + 1]?.querySelector('span')?.textContent?.trim();
                next.innerHTML = activeIndex === tabs.length - 1
                    ? `Confirm Agreement ${arrowIcon}`
                    : `Next: ${nextSection} ${arrowIcon}`;
                next.type = activeIndex === tabs.length - 1 ? 'submit' : 'button';
            }
        };

        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => setActive(index));
        });
        next?.addEventListener('click', (event) => {
            if (activeIndex < tabs.length - 1) {
                event.preventDefault();
                setActive(activeIndex + 1);
            }
        });

        form?.addEventListener('submit', (event) => {
            if (agree && !agree.checked) {
                event.preventDefault();
                card.classList.add('needs-agreement');
                agree.focus();
            }
        });

        agree?.addEventListener('change', () => {
            card.classList.remove('needs-agreement');
        });

        close?.addEventListener('click', () => {
            if (form) {
                window.location.href = @json(route('home'));
                return;
            }
            if (window.history.length > 1) {
                window.history.back();
                return;
            }

            window.location.href = @json(route('home'));
        });
    })();
</script>
