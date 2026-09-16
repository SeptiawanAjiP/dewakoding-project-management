<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $user = User::where('google_id', $googleUser->id)->first();

            if ($user) {
                Auth::login($user);

                return redirect()->intended('/admin');
            }

            $existingUser = User::where('email', $googleUser->email)->first();

            if ($existingUser) {
                $existingUser->update([
                    'google_id' => $googleUser->id,
                ]);
                Auth::login($existingUser);

                return redirect()->intended('/admin');
            }

            $newUser = User::create([
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'google_id' => $googleUser->id,
                'password' => null,
                'email_verified_at' => now(),
            ]);

            Auth::login($newUser);

            return redirect()->intended('/admin');

        } catch (Exception $e) {
            return redirect('/admin/login')->with('error', 'Something went wrong with Google authentication.');
        }
    }
}
