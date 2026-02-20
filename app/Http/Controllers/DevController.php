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
        abort_unless(app()->environment('local'), 403);

        $role = $request->input('role', 'customer');

        $allowedRoles = ['customer', 'editor', 'admin', 'superadmin'];

        abort_unless(in_array($role, $allowedRoles, true), 422);

        $user = User::whereHas('roles', fn ($q) => $q->where('name', $role))->firstOrFail();

        Auth::login($user);

        return redirect()->back();
    }
}
