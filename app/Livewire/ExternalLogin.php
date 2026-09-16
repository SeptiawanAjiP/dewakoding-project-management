<?php

namespace App\Livewire;

use App\Models\ExternalAccess;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class ExternalLogin extends Component
{
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 300;

    public $token;

    public $password;

    public $error;

    public function mount($token)
    {
        $this->token = $token;

        if (Session::get('external_authenticated_'.$token)) {
            return redirect()->route('external.dashboard', $token);
        }

        $externalAccess = ExternalAccess::where('access_token', $token)
            ->where('is_active', true)
            ->first();

        if (! $externalAccess) {
            abort(404, 'External access not found');
        }
    }

    public function authenticate()
    {
        $this->login();
    }

    public function login()
    {
        $this->error = null;

        $throttleKey = 'external-login:'.$this->token.'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->error = "Too many attempts. Please try again in {$seconds} seconds.";

            return;
        }

        $externalAccess = ExternalAccess::where('access_token', $this->token)
            ->where('is_active', true)
            ->first();

        if (! $externalAccess || ! hash_equals((string) $externalAccess->password, (string) $this->password)) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);
            $this->error = 'Invalid password';

            return;
        }

        RateLimiter::clear($throttleKey);

        $externalAccess->updateLastAccessed();

        // Set session
        Session::put([
            'external_project_id' => $externalAccess->project_id,
            'external_authenticated' => true,
            'external_authenticated_at_'.$this->token => now()->timestamp,
        ]);
        Session::put('external_authenticated_'.$this->token, true);

        return redirect()->route('external.dashboard', $this->token);
    }

    public function render()
    {
        return view('livewire.external-login')
            ->layout('layouts.external');
    }
}
