<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DevController extends Controller
{
    /** Instantly log in as a test user by role. Local env only. */
    public function loginAs(Request $request): RedirectResponse
    {
        abort_unless(app()->isLocal(), 403);

        $role = $request->input('role', 'customer');

        $testEmails = [
            'customer' => 'customer@wasetzon.test',
            'staff' => 'staff@wasetzon.test',
            'admin' => 'admin@wasetzon.test',
            'superadmin' => 'superadmin@wasetzon.test',
        ];

        abort_unless(isset($testEmails[$role]), 422);

        $user = User::where('email', $testEmails[$role])->first();

        if (! $user) {
            return redirect()->back()->with('error', __('Test user :email not found. Run: php artisan db:seed --class=RoleAndPermissionSeeder', [
                'email' => $testEmails[$role],
            ]));
        }

        Auth::login($user);

        // When coming from admin login, redirect to admin panel
        $previous = url()->previous();
        if ($previous && str_contains($previous, '/admin')) {
            return redirect('/admin');
        }

        return redirect()->back();
    }
}
