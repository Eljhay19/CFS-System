@extends('layouts.app')

@section('title', 'Thank You')
@section('body_class', 'feedback-screen thank-you-screen')

@section('content')
    <section class="thank-you-card" aria-labelledby="thank-you-title" data-thank-you-card>
        <canvas class="thank-confetti-canvas" data-thank-confetti aria-hidden="true"></canvas>

        <div class="thank-you-burst" aria-hidden="true">
            <div class="thank-check">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="m5 12.5 4.2 4.2L19 7"></path>
                </svg>
            </div>
        </div>

        <div class="thank-you-copy">
            <h1 id="thank-you-title">Thank You!</h1>
            <p class="thank-you-main">Your response has been successfully submitted.</p>
            <p class="thank-you-sub">We appreciate your time and feedback.</p>
            <p class="workflow-note">Feedback ID: <strong>{{ session('feedback.code') }}</strong></p>
        </div>

        <div class="thank-you-points" aria-label="Feedback benefits">
            <div class="thank-you-point">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="9" cy="8" r="3"></circle>
                    <path d="M3 20v-1a6 6 0 0 1 12 0v1"></path>
                    <circle cx="17" cy="9" r="2.5"></circle>
                    <path d="M15.5 15.5A5 5 0 0 1 21 20"></path>
                </svg>
                <span>Your feedback matters</span>
            </div>
            <div class="thank-you-point">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M5 20V10"></path>
                    <path d="M12 20V4"></path>
                    <path d="M19 20v-7"></path>
                    <path d="M3 20h18"></path>
                </svg>
                <span>Helps us improve</span>
            </div>
            <div class="thank-you-point">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M20.5 5.7a5.1 5.1 0 0 0-7.2 0L12 7l-1.3-1.3a5.1 5.1 0 0 0-7.2 7.2L12 21l8.5-8.1a5.1 5.1 0 0 0 0-7.2Z"></path>
                    <path class="thank-heart-fill" d="M20.5 5.7a5.1 5.1 0 0 0-7.2 0L12 7l-1.3-1.3a5.1 5.1 0 0 0-7.2 7.2L12 21l8.5-8.1a5.1 5.1 0 0 0 0-7.2Z"></path>
                </svg>
                <span>Better experience for everyone</span>
            </div>
        </div>

        <a class="button thank-home-button" href="{{ route('home') }}">
            Back to Home
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"></path><path d="m13 6 6 6-6 6"></path></svg>
        </a>
    </section>

    <script>
        (() => {
            const card = document.querySelector('[data-thank-you-card]');
            const canvas = document.querySelector('[data-thank-confetti]');
            if (!card || !canvas) {
                return;
            }

            const ctx = canvas.getContext('2d');
            if (!ctx) {
                return;
            }

            // Keep the celebration visible when reduced motion is enabled; use
            // a softer burst instead of removing it completely.
            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
            const colors = ['#f97316', '#f59e0b', '#22b78f', '#46a6da', '#ef4444', '#0f2a4a'];
            const particles = [];
            let width = 0;
            let height = 0;
            let lastTime = performance.now();
            let secondBurstAt = Infinity;
            let startedAt = lastTime;

            const resize = () => {
                const rect = card.getBoundingClientRect();
                const ratio = Math.min(window.devicePixelRatio || 1, 2);
                width = rect.width;
                height = rect.height;
                canvas.width = Math.max(1, Math.floor(width * ratio));
                canvas.height = Math.max(1, Math.floor(height * ratio));
                canvas.style.width = `${width}px`;
                canvas.style.height = `${height}px`;
                ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
            };

            const burst = (count = 160) => {
                const originX = width / 2;
                const originY = 34;
                const motionScale = reduceMotion.matches ? .78 : 1;

                for (let i = 0; i < count; i += 1) {
                    const angle = (-170 + Math.random() * 160) * (Math.PI / 180);
                    const speed = (150 + Math.random() * 230) * motionScale;
                    particles.push({
                        x: originX + (Math.random() - .5) * 28,
                        y: originY + (Math.random() - .5) * 12,
                        vx: Math.cos(angle) * speed,
                        vy: Math.sin(angle) * speed,
                        gravity: (420 + Math.random() * 150) * motionScale,
                        drag: .993,
                        color: colors[Math.floor(Math.random() * colors.length)],
                        size: 4 + Math.random() * 8,
                        width: 5 + Math.random() * 13,
                        height: 3 + Math.random() * 8,
                        rotation: Math.random() * Math.PI * 2,
                        spin: -5 + Math.random() * 10,
                        life: 1,
                        decay: .3 + Math.random() * .12,
                        shape: Math.random() > .72 ? 'circle' : 'rect',
                    });
                }

                if (particles.length > 240) {
                    particles.splice(0, particles.length - 240);
                }
            };

            const drawParticle = (particle) => {
                ctx.save();
                ctx.globalAlpha = Math.max(0, particle.life);
                ctx.translate(particle.x, particle.y);
                ctx.rotate(particle.rotation);
                ctx.fillStyle = particle.color;

                if (particle.shape === 'circle') {
                    ctx.beginPath();
                    ctx.arc(0, 0, particle.size * .48, 0, Math.PI * 2);
                    ctx.fill();
                } else {
                    ctx.fillRect(-particle.width / 2, -particle.height / 2, particle.width, particle.height);
                }

                ctx.restore();
            };

            const animate = (time) => {
                const delta = Math.min((time - lastTime) / 1000, .035);
                lastTime = time;
                ctx.clearRect(0, 0, width, height);

                if (time - startedAt >= secondBurstAt) {
                    burst(reduceMotion.matches ? 36 : 56);
                    secondBurstAt = Infinity;
                }

                for (let i = particles.length - 1; i >= 0; i -= 1) {
                    const particle = particles[i];
                    particle.vx *= particle.drag;
                    particle.vy = particle.vy * particle.drag + particle.gravity * delta;
                    particle.x += particle.vx * delta;
                    particle.y += particle.vy * delta;
                    particle.rotation += particle.spin * delta;
                    particle.life -= particle.decay * delta;

                    if (particle.life <= 0 || particle.y > height + 24 || particle.x < -24 || particle.x > width + 24) {
                        particles.splice(i, 1);
                    } else {
                        drawParticle(particle);
                    }
                }

                if (particles.length) requestAnimationFrame(animate);
            };

            resize();
            burst(reduceMotion.matches ? 112 : 176);
            secondBurstAt = 230;
            window.addEventListener('resize', resize);
            requestAnimationFrame(animate);
        })();
    </script>
@endsection
