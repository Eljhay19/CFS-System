@extends('layouts.app')

@section('title', 'CFS - Admin Login')
@section('body_class', 'login-screen')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/auth-panels.css') }}?v={{ filemtime(public_path('css/auth-panels.css')) }}">
@endpush

@section('content')
    <section class="login-stage">
        @include('auth.partials.showcase')
        @include('auth.partials.login-panel')
    </section>
    <script>
        (() => {
            const display = document.getElementById('password_display');
            const real = document.getElementById('password');
            const toggle = document.getElementById('password_toggle');
            if (!display || !real || !toggle || display.dataset.maskReady === '1') return;

            display.dataset.maskReady = '1';
            let value = '';
            let visible = false;
            const mask = () => '♥'.repeat(value.length);
            const render = () => {
                display.value = visible ? value : mask();
                real.value = value;
                display.classList.toggle('is-visible', visible);
                toggle.classList.toggle('is-visible', visible);
                toggle.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
            };

            display.addEventListener('beforeinput', (event) => {
                event.preventDefault();
                const start = display.selectionStart ?? value.length;
                const end = display.selectionEnd ?? value.length;

                if (event.inputType === 'deleteContentBackward') {
                    if (start === end && start > 0) {
                        value = value.slice(0, start - 1) + value.slice(end);
                        render();
                        display.setSelectionRange(start - 1, start - 1);
                    } else {
                        value = value.slice(0, start) + value.slice(end);
                        render();
                        display.setSelectionRange(start, start);
                    }
                    return;
                }

                if (event.inputType === 'deleteContentForward') {
                    value = start === end
                        ? value.slice(0, start) + value.slice(start + 1)
                        : value.slice(0, start) + value.slice(end);
                    render();
                    display.setSelectionRange(start, start);
                    return;
                }

                const incoming = event.data || '';
                value = value.slice(0, start) + incoming + value.slice(end);
                render();
                const next = start + incoming.length;
                display.setSelectionRange(next, next);
            });

            display.addEventListener('paste', (event) => {
                event.preventDefault();
                const paste = event.clipboardData.getData('text');
                const start = display.selectionStart ?? value.length;
                const end = display.selectionEnd ?? value.length;
                value = value.slice(0, start) + paste + value.slice(end);
                render();
                const next = start + paste.length;
                display.setSelectionRange(next, next);
            });

            toggle.addEventListener('click', () => {
                visible = !visible;
                render();
                display.focus();
                display.setSelectionRange(display.value.length, display.value.length);
            });
        })();
    </script>
@endsection
