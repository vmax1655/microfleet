<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Support\GoogleAuthenticator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Sign in')]
class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    // 2FA state
    public bool $requiresTwoFactor = false;
    public ?int $pendingUserId = null;
    public string $twoFactorCode = '';
    public bool $usingRecoveryCode = false;

    public function authenticate(): void
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ], [
            'email.required' => 'Enter the email address issued by your transport operations team.',
            'email.email' => 'That does not look like a valid email address.',
            'password.required' => 'Enter your password.',
            'password.min' => 'Passwords are at least 6 characters.',
        ]);

        $user = User::where('email', $this->email)->first();

        if (! $user || ! Hash::check($this->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Those credentials do not match our records.',
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'This account is currently deactivated. Contact your supervisor.',
            ]);
        }

        // Check if user has Google Authenticator 2FA enabled
        if ($user->hasTwoFactorEnabled()) {
            $this->pendingUserId = $user->id;
            $this->requiresTwoFactor = true;
            $this->twoFactorCode = '';
            $this->usingRecoveryCode = false;
            $this->resetErrorBag();
            return;
        }

        Auth::login($user, $this->remember);
        session()->regenerate();

        $this->redirectIntended(route('dashboard'), navigate: false);
    }

    public function verifyTwoFactor(): void
    {
        if (! $this->pendingUserId) {
            $this->cancelTwoFactor();
            return;
        }

        $user = User::findOrFail($this->pendingUserId);

        if ($this->usingRecoveryCode) {
            $this->validate([
                'twoFactorCode' => 'required|string',
            ], [
                'twoFactorCode.required' => 'Enter an emergency recovery code.',
            ]);

            $code = strtoupper(trim($this->twoFactorCode));
            $recoveryCodes = $user->two_factor_recovery_codes ?? [];

            if (! in_array($code, $recoveryCodes, true)) {
                throw ValidationException::withMessages([
                    'twoFactorCode' => 'That recovery code is invalid or has already been used.',
                ]);
            }

            // Consume single-use recovery code
            $user->forceFill([
                'two_factor_recovery_codes' => array_values(array_diff($recoveryCodes, [$code])),
            ])->save();
        } else {
            $this->validate([
                'twoFactorCode' => 'required|digits:6',
            ], [
                'twoFactorCode.required' => 'Enter the 6-digit code from Google Authenticator.',
                'twoFactorCode.digits' => 'Google Authenticator codes are exactly 6 digits.',
            ]);

            if (! GoogleAuthenticator::verifyCode($user->two_factor_secret, $this->twoFactorCode)) {
                throw ValidationException::withMessages([
                    'twoFactorCode' => 'The authenticator code is incorrect or expired. Check your Google Authenticator app.',
                ]);
            }
        }

        Auth::login($user, $this->remember);
        session()->regenerate();

        $this->redirectIntended(route('dashboard'), navigate: false);
    }

    public function cancelTwoFactor(): void
    {
        $this->requiresTwoFactor = false;
        $this->pendingUserId = null;
        $this->twoFactorCode = '';
        $this->usingRecoveryCode = false;
        $this->resetErrorBag();
    }

    public function toggleRecoveryCode(): void
    {
        $this->usingRecoveryCode = ! $this->usingRecoveryCode;
        $this->twoFactorCode = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
