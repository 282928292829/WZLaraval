<?php

namespace App\Http\Controllers;

use App\Models\OrderComment;
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
        $tab  = in_array($request->query('tab'), ['profile', 'addresses', 'activity', 'notifications'])
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
            'name'            => ['required', 'string', 'max:255'],
            'email'           => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone'           => ['nullable', 'string', 'max:30'],
            'phone_secondary' => ['nullable', 'string', 'max:30'],
        ]);

        // Prepend editable country codes when they were submitted
        if ($request->filled('phone') && $request->filled('phone_code')) {
            $validated['phone'] = rtrim($request->input('phone_code'), ' ') . $validated['phone'];
        }
        if ($request->filled('phone_secondary') && $request->filled('phone_secondary_code')) {
            $validated['phone_secondary'] = rtrim($request->input('phone_secondary_code'), ' ') . $validated['phone_secondary'];
        }

        $emailChanged = $user->email !== $validated['email'];

        $user->fill($validated);

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        UserActivityLog::fromRequest($request, [
            'user_id' => $user->id,
            'event'   => 'profile_updated',
        ]);

        return redirect()->route('account.index')->with('status', 'profile-updated');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => [
                'required',
                'string',
                'min:8',
                'max:128',
                'confirmed',
                'different:current_password',
                Password::min(8)->mixedCase()->numbers(),
            ],
            'password_confirmation' => ['required'],
        ], [
            'current_password.required'        => __('account.pw_error_current_required'),
            'current_password.current_password' => __('account.pw_error_current_wrong'),
            'password.required'                => __('account.pw_error_new_required'),
            'password.min'                     => __('account.pw_error_min'),
            'password.max'                     => __('account.pw_error_max'),
            'password.confirmed'               => __('account.pw_error_confirmed'),
            'password.different'               => __('account.pw_error_same_as_current'),
            'password.password'                => __('account.pw_error_strength'),
            'password_confirmation.required'   => __('account.pw_error_confirm_required'),
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        UserActivityLog::fromRequest($request, [
            'user_id' => $request->user()->id,
            'event'   => 'password_changed',
        ]);

        return redirect()->route('account.index')->with('status', 'password-updated');
    }

    public function storeAddress(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label'          => ['nullable', 'string', 'max:50'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone'          => ['required', 'string', 'max:30'],
            'country'        => ['nullable', 'string', 'max:100'],
            'city'           => ['nullable', 'string', 'max:100'],
            'street'         => ['nullable', 'string', 'max:255'],
            'district'       => ['nullable', 'string', 'max:255'],
            'short_address'  => ['nullable', 'string', 'max:20'],
            'address'        => ['nullable', 'string', 'max:1000'],
            'is_default'     => ['nullable', 'boolean'],
        ]);

        $validated['country'] = $validated['country'] ?: 'المملكة العربية السعودية';

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

        UserActivityLog::fromRequest($request, [
            'user_id' => $user->id,
            'event'   => 'address_added',
        ]);

        return redirect()->route('account.index', ['tab' => 'addresses'])->with('status', 'address-saved');
    }

    public function updateAddress(Request $request, UserAddress $address): RedirectResponse
    {
        abort_if($address->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'label'          => ['nullable', 'string', 'max:50'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone'          => ['required', 'string', 'max:30'],
            'country'        => ['nullable', 'string', 'max:100'],
            'city'           => ['nullable', 'string', 'max:100'],
            'street'         => ['nullable', 'string', 'max:255'],
            'district'       => ['nullable', 'string', 'max:255'],
            'short_address'  => ['nullable', 'string', 'max:20'],
            'address'        => ['nullable', 'string', 'max:1000'],
            'is_default'     => ['nullable', 'boolean'],
        ]);

        $validated['country'] = $validated['country'] ?: 'المملكة العربية السعودية';

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

        UserActivityLog::fromRequest($request, [
            'user_id' => $request->user()->id,
            'event'   => 'address_removed',
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

    public function updateNotifications(Request $request): RedirectResponse
    {
        $user = $request->user();

        $prevWhatsapp = (bool) $user->notify_whatsapp;
        $newWhatsapp  = $request->boolean('notify_whatsapp');

        $user->update([
            'notify_orders'     => true, // always on — mandatory for order notifications
            'notify_promotions' => $request->boolean('notify_promotions'),
            'notify_whatsapp'   => $newWhatsapp,
        ]);

        // Post system comment on latest active order when WhatsApp preference changes
        if ($prevWhatsapp !== $newWhatsapp) {
            $activeOrder = $user->orders()
                ->whereNotIn('status', ['cancelled', 'delivered', 'completed'])
                ->latest()
                ->first();

            if ($activeOrder) {
                $status = $newWhatsapp ? 'مفعّل' : 'معطّل';
                OrderComment::create([
                    'order_id'    => $activeOrder->id,
                    'user_id'     => null,
                    'body'        => "🔔 [نظام] العميل غيّر تفضيل إشعارات واتساب إلى: {$status}",
                    'is_internal' => true,
                ]);
            }
        }

        return redirect()->route('account.index', ['tab' => 'notifications'])->with('status', 'notifications-updated');
    }

    public function requestDeletion(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->deletion_requested) {
            return redirect()->route('account.index')->with('status', 'deletion-already-requested');
        }

        $user->update(['deletion_requested' => true]);

        // Post system comment on latest active order to alert the team
        $activeOrder = $user->orders()
            ->whereNotIn('status', ['cancelled', 'delivered', 'completed'])
            ->latest()
            ->first();

        if (!$activeOrder) {
            $activeOrder = $user->orders()->latest()->first();
        }

        if ($activeOrder) {
            OrderComment::create([
                'order_id'    => $activeOrder->id,
                'user_id'     => null,
                'body'        => '⚠️ [نظام] طلب العميل حذف حسابه. يرجى مراجعة الطلب والتواصل معه قبل تنفيذ الحذف.',
                'is_internal' => true,
            ]);
        }

        UserActivityLog::fromRequest($request, [
            'user_id' => $user->id,
            'event'   => 'deletion_requested',
        ]);

        return redirect()->route('account.index')->with('status', 'deletion-requested');
    }

    public function cancelDeletion(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (!$user->deletion_requested) {
            return redirect()->route('account.index');
        }

        $user->update(['deletion_requested' => false]);

        UserActivityLog::fromRequest($request, [
            'user_id' => $user->id,
            'event'   => 'deletion_cancelled',
        ]);

        return redirect()->route('account.index')->with('status', 'deletion-cancelled');
    }
}
