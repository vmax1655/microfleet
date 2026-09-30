@props(['title' => null])

@php
    $routeName = request()->route()?->getName();
    $activeModule = \App\Support\Nav::activeModule($routeName) ?? '';
@endphp

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · Microfleet' : 'Microfleet' }}</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-neutral-50 text-neutral-800">
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:top-3 focus:left-3 focus:rounded-[8px] focus:bg-primary-700 focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-white">
        Skip to main content
    </a>

    <div x-data="ledgerShell(@js($activeModule))"
         @keydown.escape.window="closeFlyout(); mobileOpen = false"
         class="min-h-full">
        <div class="fixed inset-x-0 top-0 z-[70] h-0.5 overflow-hidden bg-transparent pointer-events-none"
             aria-hidden="true">
            <div class="module-nav-bar"
                 :class="navigating ? 'is-loading' : (navFinished ? 'is-finished' : '')"></div>
        </div>

        {{-- Scrim for the off-canvas drawer under 1024px --}}
        <div x-show="mobileOpen"
             x-transition.opacity
             @click="mobileOpen = false"
             class="fixed inset-0 z-30 bg-neutral-950/50 lg:hidden"
             aria-hidden="true"></div>

        <x-sidebar />

        <div class="transition-[padding] duration-200"
             :class="collapsed ? 'lg:pl-[68px]' : 'lg:pl-[264px]'">

            <x-topbar :title="$title" />

            {{-- Main content area set to z-10 so topbar dropdowns (z-50) float on top of page buttons/cards --}}
            <main id="main-content"
                  data-page-surface
                  class="module-page module-entering relative z-10 mx-auto w-full max-w-content p-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    {{-- Inactivity Session Timeout Monitor --}}
    <div x-data="sessionTimeoutTracker()"
         x-init="initTracker()"
         @mousemove.window.passive="onActivity()"
         @keydown.window.passive="onActivity()"
         @click.window.passive="onActivity()"
         @scroll.window.passive="onActivity()"
         @touchstart.window.passive="onActivity()"
         x-show="showWarning"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 scale-95"
         class="fixed bottom-5 right-5 z-[999999] max-w-sm rounded-[12px] border border-amber-300 bg-white p-4 shadow-flyout text-neutral-800"
         style="z-index: 999999 !important;"
         role="alert">
        <div class="flex items-start gap-3">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-amber-100 text-amber-700">
                <x-icon name="alert-triangle" class="h-5 w-5" />
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-neutral-900">Session Expiring</p>
                <p class="mt-1 text-xs text-neutral-600">
                    You have been inactive. For your security, your session will automatically close in
                    <strong class="tabular-nums font-bold text-amber-700" x-text="countdown">60</strong> seconds.
                </p>
                <div class="mt-3 flex items-center gap-2">
                    <button type="button"
                            @click="keepAlive()"
                            class="rounded-[6px] bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-primary-700 transition-colors">
                        Stay Signed In
                    </button>
                    <a href="{{ route('logout') }}"
                       class="text-xs text-neutral-500 hover:text-neutral-800 underline">
                        Sign out now
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function sessionTimeoutTracker() {
            return {
                idleTimeoutSeconds: 900,   // 15 minutes of inactivity before warning
                warningSeconds: 60,        // 60-second warning countdown before auto logout
                lastActivityTime: Date.now(),
                lastPingTime: Date.now(),
                showWarning: false,
                countdown: 60,
                timerInterval: null,

                initTracker() {
                    this.lastActivityTime = Date.now();
                    this.lastPingTime = Date.now();
                    if (this.timerInterval) clearInterval(this.timerInterval);

                    this.timerInterval = setInterval(() => {
                        const now = Date.now();
                        const idleSeconds = Math.floor((now - this.lastActivityTime) / 1000);

                        if (idleSeconds >= this.idleTimeoutSeconds + this.warningSeconds) {
                            clearInterval(this.timerInterval);
                            window.location.href = "{{ route('logout', ['reason' => 'timeout']) }}";
                        } else if (idleSeconds >= this.idleTimeoutSeconds) {
                            this.showWarning = true;
                            this.countdown = (this.idleTimeoutSeconds + this.warningSeconds) - idleSeconds;
                        } else {
                            this.showWarning = false;
                        }
                    }, 1000);
                },

                onActivity() {
                    this.lastActivityTime = Date.now();
                    if (this.showWarning) {
                        this.showWarning = false;
                        this.pingServer();
                    }

                    // Throttle keepalive ping to server: at most once every 2 minutes while active
                    const now = Date.now();
                    if (now - this.lastPingTime > 120000) {
                        this.pingServer();
                    }
                },

                keepAlive() {
                    this.lastActivityTime = Date.now();
                    this.showWarning = false;
                    this.pingServer();
                },

                pingServer() {
                    this.lastPingTime = Date.now();
                    fetch("{{ route('session.keepalive') }}", {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin'
                    }).catch(() => {});
                }
            };
        }
    </script>

    @livewireScripts
</body>
</html>
