<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="auth-body">
        <main class="auth-shell">
            <section class="auth-visual" aria-label="Awan Gym">
                <div class="auth-orb auth-orb-one"></div>
                <div class="auth-orb auth-orb-two"></div>

                <a href="{{ route('home') }}" class="auth-brand" wire:navigate>
                    <span class="auth-brand-mark"><x-app-logo-icon /></span>
                    <span><strong>AWAN</strong> GYM</span>
                </a>

                <div class="auth-hero">
                    <span class="auth-kicker">BUILD YOUR STRONGEST SELF</span>
                    <h1>Train Strong.<br><span>Stay Consistent.</span></h1>
                    <p>Kelola membership dan program latihan dalam satu pengalaman yang sederhana.</p>

                    <div class="auth-feature-row">
                        <div><strong>01</strong><span>Status membership jelas</span></div>
                        <div><strong>02</strong><span>Program latihan terarah</span></div>
                    </div>
                </div>

                <p class="auth-visual-footer">AWAN GYM · STRONGER EVERY DAY</p>
            </section>

            <section class="auth-form-side">
                <div class="auth-mobile-brand">
                    <span class="auth-brand-mark"><x-app-logo-icon /></span>
                    <span><strong>AWAN</strong> GYM</span>
                </div>

                <div class="auth-form-wrap">
                    {{ $slot }}
                </div>

                <p class="auth-copyright">© {{ date('Y') }} Awan Gym. All rights reserved.</p>
            </section>
        </main>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
