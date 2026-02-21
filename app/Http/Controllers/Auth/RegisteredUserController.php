<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationWelcome;
use App\Models\AdCampaign;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::min(4)],
        ]);

        // Derive display name from the part before @ (matches WordPress behavior)
        $name = ucfirst(preg_replace('/[^a-zA-Z0-9]/', ' ', explode('@', $request->email)[0]));

        // Resolve ad campaign attribution from utm_campaign / campaign query param
        $campaign = AdCampaign::resolveFromRequest($request);

        $user = User::create([
            'name'             => trim($name) ?: $request->email,
            'email'            => $request->email,
            'password'         => Hash::make($request->password),
            'ad_campaign_id'   => $campaign?->id,
            'google_click_id'  => $request->query('gclid'),
        ]);

        $user->assignRole('customer');

        event(new Registered($user));

        Auth::login($user);

        // Send welcome email if email sending is enabled
        if (Setting::get('email_enabled', false) && Setting::get('email_welcome', false)) {
            Mail::to($user->email)->queue(new RegistrationWelcome($user));
        }

        return redirect(route('register.success', absolute: false));
    }
}
