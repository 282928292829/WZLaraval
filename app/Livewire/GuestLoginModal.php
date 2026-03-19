<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\On;
use Livewire\Component;

class GuestLoginModal extends Component
{
    public bool $showLoginModal = false;

    /** 'submit' | 'attach' — controls modal copy and post-login action */
    public string $loginModalReason = 'submit';

    public string $modalStep = 'email';

    public string $modalEmail = '';

    public string $modalPassword = '';

    public string $modalName = '';

    public string $modalPhone = '';

    public string $modalError = '';

    public string $modalSuccess = '';

    #[On('open-login-modal')]
    public function openModal(string $reason = 'submit'): void
    {
        $this->loginModalReason = $reason;
        $this->showLoginModal = true;
        $this->modalStep = 'email';
        $this->modalError = '';
        $this->modalSuccess = '';
    }

    public function checkModalEmail(): void
    {
        $this->resetValidation('modalEmail');
        $this->modalError = '';
        $this->modalSuccess = '';

        $this->validate(['modalEmail' => 'required|email'], [], ['modalEmail' => __('Email')]);

        $exists = User::where('email', $this->modalEmail)->exists();
        $this->modalStep = $exists ? 'login' : 'register';
    }

    public function loginFromModal(): void
    {
        $this->modalError = '';

        $this->validate([
            'modalEmail' => 'required|email',
            'modalPassword' => 'required',
        ], [], [
            'modalEmail' => __('Email'),
            'modalPassword' => __('Password'),
        ]);

        if (Auth::attempt(['email' => $this->modalEmail, 'password' => $this->modalPassword], true)) {
            $reason = $this->loginModalReason;
            $this->showLoginModal = false;
            $this->modalPassword = '';
            $this->loginModalReason = 'submit';
            $this->dispatch('user-logged-in', reason: $reason);
        } else {
            $this->modalError = __('auth.failed');
        }
    }

    public function registerFromModal(): void
    {
        $this->modalError = '';

        $this->validate([
            'modalEmail' => 'required|email|unique:users,email',
            'modalPassword' => 'required|min:4',
        ], [], [
            'modalEmail' => __('Email'),
            'modalPassword' => __('Password'),
        ]);

        $name = strstr($this->modalEmail, '@', true) ?: 'Customer';
        $name = trim($name) !== '' ? $name : 'Customer';

        $user = User::create([
            'name' => $name,
            'email' => $this->modalEmail,
            'phone' => null,
            'password' => bcrypt($this->modalPassword),
        ]);

        $user->assignRole('customer');
        Auth::login($user, true);

        $reason = $this->loginModalReason;
        $this->showLoginModal = false;
        $this->modalPassword = '';
        $this->loginModalReason = 'submit';
        $this->dispatch('user-logged-in', reason: $reason);
    }

    public function sendModalResetLink(): void
    {
        $this->modalError = '';
        $this->modalSuccess = '';

        $this->validate(['modalEmail' => 'required|email'], [], ['modalEmail' => __('Email')]);

        $status = Password::sendResetLink(['email' => $this->modalEmail]);

        if ($status === Password::RESET_LINK_SENT) {
            $this->modalSuccess = __('order_form.reset_link_sent');
        } else {
            $this->modalError = __('passwords.user');
        }
    }

    public function setModalStep(string $step): void
    {
        $this->modalStep = $step;
        $this->modalError = '';
        $this->modalSuccess = '';
    }

    public function closeModal(): void
    {
        $this->showLoginModal = false;
        $this->loginModalReason = 'submit';
        $this->modalStep = 'email';
        $this->modalEmail = '';
        $this->modalPassword = '';
        $this->modalError = '';
        $this->modalSuccess = '';
        $this->dispatch('modal-closed');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.guest-login-modal');
    }
}
