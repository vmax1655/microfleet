<?php

namespace App\Livewire\Auth;

use App\Support\GoogleAuthenticator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Two-Factor Authentication')]
class TwoFactorSettings extends Component
{
    // Setup state
    public bool $isSettingUp = false;
    public string $secret = '';
    public string $qrCodeUrl = '';
    public string $formattedSecret = '';
    public string $confirmCode = '';

    // Management state
    public array $displayedRecoveryCodes = [];

    // Notifications
    public ?string $bannerMessage = null;

    public function mount(): void
    {
        $this->prepareSetup();
    }

    public function prepareSetup(): void
    {
        $user = auth()->user();
        if ($user && ! $user->hasTwoFactorEnabled()) {
            $this->secret = GoogleAuthenticator::generateSecret();
            $this->qrCodeUrl = GoogleAuthenticator::getQrCodeUrl($user->email, $this->secret, 'Microfleet');
            $this->formattedSecret = GoogleAuthenticator::formatSecret($this->secret);
        }
    }

    /**
     * Toggle 2FA on or off.
     */
    public function toggleStatus(): void
    {
        $user = auth()->user();
        if ($user->hasTwoFactorEnabled()) {
            $this->dispatch('open-modal', 'disable-2fa-modal');
        } else {
            $this->startSetup();
        }
    }

    public function startSetup(): void
    {
        $this->prepareSetup();
        $this->isSettingUp = true;
        $this->confirmCode = '';
        $this->resetErrorBag();
    }

    public function cancelSetup(): void
    {
        $this->isSettingUp = false;
        $this->confirmCode = '';
        $this->resetErrorBag();
    }

    /**
     * Enable 2FA after scanning QR and entering 6-digit confirmation code.
     */
    public function confirmTwoFactor(): void
    {
        $this->validate([
            'confirmCode' => 'required|digits:6',
        ], [
            'confirmCode.required' => 'Enter the 6-digit code shown in Google Authenticator.',
            'confirmCode.digits' => 'The code must be exactly 6 digits.',
        ]);

        if (! GoogleAuthenticator::verifyCode($this->secret, $this->confirmCode)) {
            throw ValidationException::withMessages([
                'confirmCode' => 'The code does not match. Please ensure Google Authenticator time is synced and try again.',
            ]);
        }

        $user = auth()->user();
        $codes = GoogleAuthenticator::generateRecoveryCodes(8);

        $user->forceFill([
            'two_factor_secret' => $this->secret,
            'two_factor_recovery_codes' => $codes,
            'two_factor_confirmed_at' => now(),
        ])->save();

        $this->isSettingUp = false;
        $this->confirmCode = '';
        $this->displayedRecoveryCodes = $codes;
        $this->bannerMessage = 'Google Authenticator Two-Factor Authentication is now ENABLED.';
        $this->dispatch('open-modal', 'recovery-codes-modal');
    }

    /**
     * Quick Enable 2FA in 1 click (useful for rapid testing / instant protection).
     */
    public function quickEnable(): void
    {
        $user = auth()->user();
        if (empty($this->secret)) {
            $this->secret = GoogleAuthenticator::generateSecret();
        }

        $codes = GoogleAuthenticator::generateRecoveryCodes(8);

        $user->forceFill([
            'two_factor_secret' => $this->secret,
            'two_factor_recovery_codes' => $codes,
            'two_factor_confirmed_at' => now(),
        ])->save();

        $this->isSettingUp = false;
        $this->confirmCode = '';
        $this->displayedRecoveryCodes = $codes;
        $this->bannerMessage = 'Google Authenticator Two-Factor Authentication has been ENABLED for your account!';
        $this->dispatch('open-modal', 'recovery-codes-modal');
    }

    /**
     * Disable 2FA.
     */
    public function disableTwoFactor(): void
    {
        $user = auth()->user();

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $this->prepareSetup();
        $this->bannerMessage = 'Google Authenticator Two-Factor Authentication has been DISABLED.';
        $this->dispatch('close-modal');
    }

    public function regenerateRecoveryCodes(): void
    {
        $user = auth()->user();
        abort_unless($user->hasTwoFactorEnabled(), 403);

        $codes = GoogleAuthenticator::generateRecoveryCodes(8);

        $user->forceFill([
            'two_factor_recovery_codes' => $codes,
        ])->save();

        $this->displayedRecoveryCodes = $codes;
        $this->bannerMessage = 'New emergency recovery codes have been generated. Old codes are now invalid.';
        $this->dispatch('open-modal', 'recovery-codes-modal');
    }

    public function render()
    {
        $user = auth()->user();
        $currentTotp = null;
        if ($user && $user->hasTwoFactorEnabled()) {
            $currentTotp = GoogleAuthenticator::getCode($user->two_factor_secret);
        }

        return view('livewire.auth.two-factor-settings', [
            'user' => $user,
            'currentTotp' => $currentTotp,
        ]);
    }
}
