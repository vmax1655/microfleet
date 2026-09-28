<div>
    <x-breadcrumb />

    <x-page-header
        title="Two-Factor Authentication (2FA)"
        subtitle="Manage Google Authenticator protection for your Microfleet transport account.">
    </x-page-header>

    @if($bannerMessage)
        <div class="mt-4 flex items-center justify-between rounded-[8px] border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="alert">
            <div class="flex items-center gap-2">
                <x-icon name="check-circle" class="h-4 w-4 text-emerald-600" />
                <span>{{ $bannerMessage }}</span>
            </div>
            <button type="button" wire:click="$set('bannerMessage', null)" class="text-emerald-700 hover:text-emerald-900">
                <x-icon name="x" class="h-4 w-4" />
            </button>
        </div>
    @endif

    <div class="mt-6 max-w-4xl space-y-6">
        {{-- Master Enable / Disable Toggle Card --}}
        <x-card>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-4">
                    <div class="grid h-12 w-12 shrink-0 place-items-center rounded-full {{ $user->hasTwoFactorEnabled() ? 'bg-emerald-100 text-emerald-700' : 'bg-neutral-100 text-neutral-500' }}">
                        <x-icon name="{{ $user->hasTwoFactorEnabled() ? 'shield-check' : 'shield' }}" class="h-6 w-6" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h3 class="text-base font-semibold text-neutral-800">Google Authenticator Status</h3>
                            @if($user->hasTwoFactorEnabled())
                                <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">Enabled (Active)</span>
                            @else
                                <span class="rounded-full bg-neutral-200 px-2.5 py-0.5 text-xs font-semibold text-neutral-600">Disabled (Off)</span>
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-neutral-600">
                            @if($user->hasTwoFactorEnabled())
                                Two-factor authentication is currently <strong>protecting your account</strong>. Each sign-in requires your password plus a 6-digit code from Google Authenticator.
                            @else
                                Two-factor authentication is currently <strong>turned off</strong>. You only need your email and password to sign in.
                            @endif
                        </p>
                    </div>
                </div>

                {{-- Interactive Switch Control --}}
                <div class="flex items-center gap-3 shrink-0">
                    <span class="text-xs font-medium {{ $user->hasTwoFactorEnabled() ? 'text-emerald-700 font-bold' : 'text-neutral-500' }}">
                        {{ $user->hasTwoFactorEnabled() ? '2FA ON' : '2FA OFF' }}
                    </span>
                    <button type="button"
                            @if($user->hasTwoFactorEnabled())
                                @click="$dispatch('open-modal', 'disable-2fa-modal')"
                            @else
                                wire:click="startSetup"
                            @endif
                            class="relative inline-flex h-7 w-12 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 {{ $user->hasTwoFactorEnabled() ? 'bg-emerald-600' : 'bg-neutral-300' }}"
                            role="switch"
                            aria-checked="{{ $user->hasTwoFactorEnabled() ? 'true' : 'false' }}"
                            title="{{ $user->hasTwoFactorEnabled() ? 'Click to Disable 2FA' : 'Click to Enable 2FA' }}">
                        <span class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $user->hasTwoFactorEnabled() ? 'translate-x-5' : 'translate-x-0' }}"></span>
                    </button>
                </div>
            </div>
        </x-card>

        @if($user->hasTwoFactorEnabled())
            {{-- Details when 2FA is ENABLED --}}
            <x-card>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-neutral-200 pb-4">
                    <div>
                        <h4 class="text-sm font-semibold text-neutral-800">Active Protection Details</h4>
                        <p class="mt-0.5 text-xs text-neutral-500">
                            Activated on {{ $user->two_factor_confirmed_at?->format('M d, Y · h:i A') }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-btn variant="danger" size="sm" icon="x" @click="$dispatch('open-modal', 'disable-2fa-modal')">
                            Disable Google Authenticator
                        </x-btn>
                    </div>
                </div>

                {{-- Live code helper for testing / convenience --}}
                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="rounded-[8px] border border-neutral-200 bg-neutral-50 p-3.5">
                        <span class="text-xs font-medium text-neutral-600">Configured Secret Key:</span>
                        <p class="mt-1 font-mono text-sm font-semibold tracking-wider text-neutral-900">
                            {{ \App\Support\GoogleAuthenticator::formatSecret($user->two_factor_secret) }}
                        </p>
                        <p class="mt-1 text-[11px] text-neutral-500">
                            Add this key to Google Authenticator on any additional device.
                        </p>
                    </div>

                    <div class="rounded-[8px] border border-emerald-200 bg-emerald-50/70 p-3.5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-emerald-900">Current Valid TOTP Code:</span>
                            <span class="rounded bg-emerald-200 px-1.5 py-0.5 text-[10px] font-bold text-emerald-800">Updates every 30s</span>
                        </div>
                        <p class="mt-1 font-mono text-2xl font-bold tracking-[0.25em] text-emerald-800">
                            {{ $currentTotp ?? '------' }}
                        </p>
                        <p class="mt-1 text-[11px] text-emerald-700">
                            Matches the code shown on your phone's Google Authenticator app.
                        </p>
                    </div>
                </div>

                {{-- Recovery Codes section --}}
                <div class="mt-6 rounded-[10px] border border-neutral-200 bg-neutral-50/80 p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h4 class="text-sm font-semibold text-neutral-800">Emergency Recovery Codes</h4>
                            <p class="mt-0.5 text-xs text-neutral-500">
                                Single-use backup codes if you ever lose your phone.
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-btn size="sm" icon="eye" @click="$dispatch('open-modal', 'recovery-codes-modal')">
                                View Codes ({{ count($user->two_factor_recovery_codes ?? []) }} available)
                            </x-btn>
                            <x-btn size="sm" icon="refresh-cw" wire:click="regenerateRecoveryCodes">
                                Regenerate
                            </x-btn>
                        </div>
                    </div>
                </div>
            </x-card>

        @elseif($isSettingUp)
            {{-- Guided Setup in Progress --}}
            <x-card>
                <div class="border-b border-neutral-200 pb-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="grid h-8 w-8 place-items-center rounded-full bg-primary-100 text-primary-700">
                                <x-icon name="smartphone" class="h-4 w-4" />
                            </span>
                            <h3 class="text-base font-semibold text-neutral-800">Link Google Authenticator</h3>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-btn size="sm" variant="primary" icon="check" wire:click="quickEnable">
                                Quick Enable (1-Click)
                            </x-btn>
                            <x-btn size="sm" wire:click="cancelSetup">Cancel</x-btn>
                        </div>
                    </div>
                    <p class="mt-1 text-xs text-neutral-500">
                        Scan the QR code with your phone or use 1-Click Quick Enable.
                    </p>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-8 md:grid-cols-2">
                    {{-- Step 1: Scan QR --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-2">
                            <span class="grid h-6 w-6 place-items-center rounded-full bg-primary-600 text-xs font-bold text-white">1</span>
                            <h4 class="text-sm font-semibold text-neutral-800">Scan QR Code</h4>
                        </div>
                        <p class="text-xs text-neutral-600">
                            Open the <strong>Google Authenticator</strong> app on your mobile device, tap <strong>+</strong>, and scan this code:
                        </p>

                        <div class="flex flex-col items-center justify-center rounded-[10px] border border-neutral-200 bg-white p-4 shadow-xs">
                            <img src="{{ $qrCodeUrl }}"
                                 alt="Google Authenticator QR Code"
                                 class="h-48 w-48 rounded-[6px]"
                                 loading="lazy" />
                            <span class="mt-2 text-[11px] text-neutral-400">Microfleet: {{ $user->email }}</span>
                        </div>

                        <div class="rounded-[8px] border border-neutral-200 bg-neutral-50 p-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium text-neutral-600">Manual entry key:</span>
                                <button type="button"
                                        x-data="{ copied: false }"
                                        @click="navigator.clipboard.writeText('{{ $secret }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                        class="flex items-center gap-1 text-xs font-semibold text-primary-700 hover:text-primary-800">
                                    <x-icon name="copy" class="h-3.5 w-3.5" />
                                    <span x-text="copied ? 'Copied!' : 'Copy key'"></span>
                                </button>
                            </div>
                            <p class="mt-1 font-mono text-xs font-semibold tracking-wider text-neutral-800">{{ $formattedSecret }}</p>
                        </div>
                    </div>

                    {{-- Step 2: Verify Code --}}
                    <div class="flex flex-col justify-between space-y-4">
                        <div class="space-y-4">
                            <div class="flex items-center gap-2">
                                <span class="grid h-6 w-6 place-items-center rounded-full bg-primary-600 text-xs font-bold text-white">2</span>
                                <h4 class="text-sm font-semibold text-neutral-800">Verify 6-Digit Code</h4>
                            </div>
                            <p class="text-xs text-neutral-600">
                                Enter the 6-digit code currently shown in your Google Authenticator app:
                            </p>

                            <form wire:submit="confirmTwoFactor" id="confirm-2fa-form" class="space-y-4">
                                <div>
                                    <label for="confirmCode" class="block text-xs font-medium text-neutral-700">
                                        6-digit authenticator code <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                           id="confirmCode"
                                           wire:model="confirmCode"
                                           maxlength="6"
                                           inputmode="numeric"
                                           autocomplete="one-time-code"
                                           placeholder="000000"
                                           class="mt-1 block w-full rounded-[8px] border border-neutral-300 px-4 py-2.5 text-center font-mono text-xl tracking-[0.3em] text-neutral-800 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-600/30 {{ $errors->has('confirmCode') ? 'border-danger ring-danger/25' : '' }}" />
                                    @error('confirmCode')
                                        <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                                    @enderror
                                </div>
                            </form>
                        </div>

                        <div class="space-y-2 border-t border-neutral-200 pt-4">
                            <x-btn variant="primary" icon="check" type="submit" form="confirm-2fa-form" class="w-full">
                                Verify & Enable 2FA
                            </x-btn>
                            <x-btn variant="secondary" icon="sparkles" wire:click="quickEnable" class="w-full">
                                Or Quick Enable (Skip 6-digit check)
                            </x-btn>
                        </div>
                    </div>
                </div>
            </x-card>

        @else
            {{-- Actions when 2FA is DISABLED --}}
            <x-card>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h4 class="text-sm font-semibold text-neutral-800">Enable Google Authenticator</h4>
                        <p class="mt-1 text-xs text-neutral-500">
                            Choose between guided QR code setup or instant 1-click activation.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <x-btn variant="primary" icon="smartphone" wire:click="startSetup">
                            Set Up via QR Code
                        </x-btn>
                        <x-btn variant="secondary" icon="check" wire:click="quickEnable">
                            1-Click Quick Enable
                        </x-btn>
                    </div>
                </div>
            </x-card>
        @endif
    </div>

    {{-- Modal: Emergency Recovery Codes --}}
    <x-modal name="recovery-codes-modal" title="Emergency Recovery Codes" subtitle="Store these codes safely. Each code can only be used once." icon="key-round" size="md">
        @php $codes = $displayedRecoveryCodes ?: ($user->two_factor_recovery_codes ?? []); @endphp
        <div class="space-y-4">
            <p class="text-xs text-neutral-600">
                If you lose access to Google Authenticator, enter one of these backup recovery codes during sign-in to access your account.
            </p>

            <div class="grid grid-cols-2 gap-2 rounded-[8px] border border-neutral-200 bg-neutral-50 p-4 font-mono text-sm font-semibold tracking-wider text-neutral-800"
                 id="recovery-codes-container">
                @foreach($codes as $code)
                    <div class="rounded border border-neutral-200 bg-white px-2 py-1.5 text-center">
                        {{ $code }}
                    </div>
                @endforeach
            </div>

            <div class="flex items-center justify-between">
                <span class="text-xs text-neutral-500">{{ count($codes) }} recovery codes available</span>
                <button type="button"
                        x-data="{ copied: false }"
                        @click="navigator.clipboard.writeText('{{ implode("\n", $codes) }}'); copied = true; setTimeout(() => copied = false, 2000)"
                        class="flex items-center gap-1.5 text-xs font-semibold text-primary-700 hover:text-primary-800">
                    <x-icon name="copy" class="h-3.5 w-3.5" />
                    <span x-text="copied ? 'Copied all!' : 'Copy all codes'"></span>
                </button>
            </div>
        </div>

        <x-slot:footer>
            <x-btn variant="primary" @click="$dispatch('close-modal')">Done</x-btn>
        </x-slot:footer>
    </x-modal>

    {{-- Modal: Disable 2FA Confirmation --}}
    <x-modal name="disable-2fa-modal" title="Disable Google Authenticator?" subtitle="This will remove the two-factor authentication requirement from your account." icon="alert-triangle" tone="danger" size="md">
        <div class="space-y-3">
            <p class="text-sm text-neutral-600">
                Are you sure you want to turn off two-factor authentication?
            </p>
            <div class="rounded-[8px] border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                <strong>Note:</strong> Once disabled, anyone with your password will be able to sign in without an authenticator code.
            </div>
        </div>

        <x-slot:footer>
            <x-btn @click="$dispatch('close-modal')">Cancel</x-btn>
            <x-btn variant="danger" icon="trash-2" wire:click="disableTwoFactor">Yes, Disable 2FA</x-btn>
        </x-slot:footer>
    </x-modal>
</div>
