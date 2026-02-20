<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use App\Models\UserActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $tab  = in_array($request->query('tab'), ['profile', 'addresses', 'activity'])
            ? $request->query('tab')
            : 'profile';

        $addresses = $user->addresses()
            ->orderByDesc('is_default')
            ->orderBy('created_at')
            ->get();

        $activityLogs = UserActivityLog::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(15)
            ->appends(['tab' => 'activity']);

        return view('account.index', compact('user', 'tab', 'addresses', 'activityLogs'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $emailChanged = $user->email !== $validated['email'];

        $user->fill($validated);

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        UserActivityLog::create([
            'user_id'    => $user->id,
            'event'      => 'profile_updated',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('account.index')->with('status', 'profile-updated');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        UserActivityLog::create([
            'user_id'    => $request->user()->id,
            'event'      => 'password_changed',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('account.index')->with('status', 'password-updated');
    }

    public function storeAddress(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label'          => ['nullable', 'string', 'max:50'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'phone'          => ['nullable', 'string', 'max:30'],
            'country'        => ['required', 'string', 'max:100'],
            'city'           => ['required', 'string', 'max:100'],
            'address'        => ['required', 'string', 'max:1000'],
            'is_default'     => ['nullable', 'boolean'],
        ]);

        $user = $request->user();

        $isDefault = !empty($validated['is_default']);

        if ($isDefault) {
            $user->addresses()->update(['is_default' => false]);
        }

        // First address is always default
        if ($user->addresses()->count() === 0) {
            $isDefault = true;
        }

        $validated['is_default'] = $isDefault;

        $user->addresses()->create($validated);

        UserActivityLog::create([
            'user_id'    => $user->id,
            'event'      => 'address_added',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('account.index', ['tab' => 'addresses'])->with('status', 'address-saved');
    }

    public function updateAddress(Request $request, UserAddress $address): RedirectResponse
    {
        abort_if($address->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'label'          => ['nullable', 'string', 'max:50'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'phone'          => ['nullable', 'string', 'max:30'],
            'country'        => ['required', 'string', 'max:100'],
            'city'           => ['required', 'string', 'max:100'],
            'address'        => ['required', 'string', 'max:1000'],
            'is_default'     => ['nullable', 'boolean'],
        ]);

        $isDefault = !empty($validated['is_default']);

        if ($isDefault) {
            $request->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $validated['is_default'] = $isDefault;

        $address->update($validated);

        return redirect()->route('account.index', ['tab' => 'addresses'])->with('status', 'address-saved');
    }

    public function destroyAddress(Request $request, UserAddress $address): RedirectResponse
    {
        abort_if($address->user_id !== $request->user()->id, 403);

        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $request->user()->addresses()->orderBy('created_at')->first()?->update(['is_default' => true]);
        }

        UserActivityLog::create([
            'user_id'    => $request->user()->id,
            'event'      => 'address_removed',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('account.index', ['tab' => 'addresses'])->with('status', 'address-deleted');
    }

    public function setDefaultAddress(Request $request, UserAddress $address): RedirectResponse
    {
        abort_if($address->user_id !== $request->user()->id, 403);

        $request->user()->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return redirect()->route('account.index', ['tab' => 'addresses'])->with('status', 'address-saved');
    }
}
