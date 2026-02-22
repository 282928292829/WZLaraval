<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderComment;
use App\Models\UserActivityLog;
use App\Models\UserAddress;
use App\Models\UserBalance;
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
        $tab = in_array($request->query('tab'), ['profile', 'addresses', 'activity', 'notifications', 'balance'])
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

        $balanceTransactions = UserBalance::where('user_id', $user->id)
            ->with('creator:id,name')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(25)
            ->appends(['tab' => 'balance']);

        $balanceTotals = UserBalance::totalsForUser($user->id);

        $orderStats = [
            'total' => Order::where('user_id', $user->id)->count(),
            'active' => Order::where('user_id', $user->id)
                ->whereNotIn('status', ['cancelled', 'delivered', 'completed'])
                ->count(),
            'shipped' => Order::where('user_id', $user->id)
                ->where('status', 'shipped')
                ->count(),
            'cancelled' => Order::where('user_id', $user->id)
                ->where('status', 'cancelled')
                ->count(),
        ];

        return view('account.index', compact('user', 'tab', 'addresses', 'activityLogs', 'balanceTransactions', 'balanceTotals', 'orderStats'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validateWithBag('updateProfile', [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9]+$/'],
            'phone_secondary' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9]*$/'],
        ], [
            'name.required' => __('account.validation_name_required'),
            'name.max' => __('account.validation_name_max'),
            'email.required' => __('account.validation_email_required'),
            'email.email' => __('account.validation_email_invalid'),
            'email.max' => __('account.validation_email_max'),
            'email.unique' => __('account.validation_email_taken'),
            'phone.regex' => __('account.validation_phone_digits'),
            'phone.max' => __('account.validation_phone_max'),
            'phone_secondary.regex' => __('account.validation_phone_digits'),
            'phone_secondary.max' => __('account.validation_phone_max'),
        ]);

        // Prepend editable country codes when they were submitted
        if ($request->filled('phone') && $request->filled('phone_code')) {
            $validated['phone'] = rtrim($request->input('phone_code'), ' ').$validated['phone'];
        }
        if ($request->filled('phone_secondary') && $request->filled('phone_secondary_code')) {
            $validated['phone_secondary'] = rtrim($request->input('phone_secondary_code'), ' ').$validated['phone_secondary'];
        }

        $emailChanged = $user->email !== $validated['email'];

        $user->fill($validated);

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        UserActivityLog::fromRequest($request, [
            'user_id' => $user->id,
            'event' => 'profile_updated',
        ]);

        // Post system comment on latest active order to alert the team
        $activeOrder = $user->orders()
            ->whereNotIn('status', ['cancelled', 'delivered', 'completed'])
            ->latest()
            ->first();

        if ($activeOrder) {
            $changes = array_filter([
                $emailChanged ? __('account.comment_email_changed') : null,
            ]);

            $changeList = implode('، ', $changes) ?: __('account.comment_profile_general');

            OrderComment::create([
                'order_id' => $activeOrder->id,
                'user_id' => null,
                'body' => __('account.comment_profile_updated', ['changes' => $changeList]),
                'is_internal' => true,
            ]);
        }

        return redirect()->route('account.index')->with('status', 'profile-updated');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => [
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
            'current_password.required' => __('account.pw_error_current_required'),
            'current_password.current_password' => __('account.pw_error_current_wrong'),
            'password.required' => __('account.pw_error_new_required'),
            'password.min' => __('account.pw_error_min'),
            'password.max' => __('account.pw_error_max'),
            'password.confirmed' => __('account.pw_error_confirmed'),
            'password.different' => __('account.pw_error_same_as_current'),
            'password.password' => __('account.pw_error_strength'),
            'password_confirmation.required' => __('account.pw_error_confirm_required'),
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        UserActivityLog::fromRequest($request, [
            'user_id' => $request->user()->id,
            'event' => 'password_changed',
        ]);

        return redirect()->route('account.index')->with('status', 'password-updated');
    }

    public function storeAddress(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('storeAddress', [
            'label' => ['nullable', 'string', 'max:50'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9]+$/'],
            'country' => ['nullable', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'street' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'short_address' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:1000'],
            'is_default' => ['nullable', 'boolean'],
        ], [
            'label.max' => __('account.validation_label_max'),
            'recipient_name.required' => __('account.validation_recipient_required'),
            'recipient_name.max' => __('account.validation_recipient_max'),
            'phone.required' => __('account.validation_address_phone_required'),
            'phone.regex' => __('account.validation_phone_digits'),
            'phone.max' => __('account.validation_phone_max'),
            'city.required' => __('account.validation_city_required'),
            'city.max' => __('account.validation_city_max'),
            'street.max' => __('account.validation_street_max'),
            'district.max' => __('account.validation_district_max'),
            'short_address.max' => __('account.validation_short_address_max'),
            'address.max' => __('account.validation_address_max'),
        ]);

        $user = $request->user();

        $isDefault = ! empty($validated['is_default']);

        if ($isDefault) {
            $user->addresses()->update(['is_default' => false]);
        }

        // First address is always default
        if ($user->addresses()->count() === 0) {
            $isDefault = true;
        }

        $validated['is_default'] = $isDefault;

        $newAddress = $user->addresses()->create($validated);

        // Sync phone to user profile when user has none
        if (! empty($validated['phone']) && empty($user->phone)) {
            $user->update(['phone' => $validated['phone']]);
        }

        UserActivityLog::fromRequest($request, [
            'user_id' => $user->id,
            'event' => 'address_added',
        ]);

        // If submitted from an order page, snapshot the new address onto that order
        $orderId = (int) $request->input('_order_id');
        if ($orderId) {
            $order = Order::where('id', $orderId)->where('user_id', $user->id)->first();
            if ($order && ! $order->shipping_address_snapshot) {
                $order->update([
                    'shipping_address_id' => $newAddress->id,
                    'shipping_address_snapshot' => $newAddress->only([
                        'label', 'recipient_name', 'phone', 'country',
                        'city', 'street', 'district', 'short_address', 'address',
                    ]),
                ]);
            }
        }

        $redirectBack = $request->input('_redirect_back');
        if ($redirectBack && str_starts_with($redirectBack, url('/'))) {
            return redirect($redirectBack)->with('success', __('orders.address_saved_order_updated'));
        }

        return redirect()->route('account.index', ['tab' => 'addresses'])->with('status', 'address-saved');
    }

    public function updateAddress(Request $request, UserAddress $address): RedirectResponse
    {
        abort_if($address->user_id !== $request->user()->id, 403);

        $validated = $request->validateWithBag('updateAddress', [
            'label' => ['nullable', 'string', 'max:50'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9]+$/'],
            'country' => ['nullable', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'street' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'short_address' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:1000'],
            'is_default' => ['nullable', 'boolean'],
        ], [
            'label.max' => __('account.validation_label_max'),
            'recipient_name.required' => __('account.validation_recipient_required'),
            'recipient_name.max' => __('account.validation_recipient_max'),
            'phone.required' => __('account.validation_address_phone_required'),
            'phone.regex' => __('account.validation_phone_digits'),
            'phone.max' => __('account.validation_phone_max'),
            'city.required' => __('account.validation_city_required'),
            'city.max' => __('account.validation_city_max'),
            'street.max' => __('account.validation_street_max'),
            'district.max' => __('account.validation_district_max'),
            'short_address.max' => __('account.validation_short_address_max'),
            'address.max' => __('account.validation_address_max'),
        ]);

        $isDefault = ! empty($validated['is_default']);

        if ($isDefault) {
            $request->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $validated['is_default'] = $isDefault;

        $address->update($validated);

        // Sync phone to user profile when user has none
        $user = $request->user();
        if (! empty($validated['phone']) && empty($user->phone)) {
            $user->update(['phone' => $validated['phone']]);
        }

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
            'event' => 'address_removed',
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
        $newWhatsapp = $request->boolean('notify_whatsapp');

        $user->update([
            'notify_orders' => true, // always on — mandatory for order notifications
            'notify_promotions' => $request->boolean('notify_promotions'),
            'notify_whatsapp' => $newWhatsapp,
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
                    'order_id' => $activeOrder->id,
                    'user_id' => null,
                    'body' => "🔔 [نظام] العميل غيّر تفضيل إشعارات واتساب إلى: {$status}",
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

        if (! $activeOrder) {
            $activeOrder = $user->orders()->latest()->first();
        }

        if ($activeOrder) {
            OrderComment::create([
                'order_id' => $activeOrder->id,
                'user_id' => null,
                'body' => '⚠️ [نظام] طلب العميل حذف حسابه. يرجى مراجعة الطلب والتواصل معه قبل تنفيذ الحذف.',
                'is_internal' => true,
            ]);
        }

        UserActivityLog::fromRequest($request, [
            'user_id' => $user->id,
            'event' => 'deletion_requested',
        ]);

        return redirect()->route('account.index')->with('status', 'deletion-requested');
    }

    public function requestEmailChange(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validateWithBag('emailChange', [
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ], [
            'email.required' => __('account.validation_email_required'),
            'email.email' => __('account.validation_email_invalid'),
            'email.unique' => __('account.validation_email_taken'),
        ]);

        $code = (string) random_int(100000, 999999);

        $user->update([
            'email_change_pending' => $validated['email'],
            'email_change_code' => $code,
            'email_change_expires_at' => now()->addMinutes(15),
        ]);

        // TODO: send $code via email when SMTP is configured
        // For now, show the code in the session flash so staff can relay it manually
        session()->flash('email_change_code_debug', $code);

        UserActivityLog::fromRequest($request, [
            'user_id' => $user->id,
            'event' => 'email_change_requested',
        ]);

        return redirect()->route('account.index', ['tab' => 'profile'])
            ->with('status', 'email-change-requested');
    }

    public function verifyEmailChange(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validateWithBag('emailChange', [
            'code' => ['required', 'string', 'size:6'],
        ]);

        if (
            ! $user->email_change_pending ||
            ! $user->email_change_code ||
            ! $user->email_change_expires_at ||
            now()->gt($user->email_change_expires_at)
        ) {
            return redirect()->route('account.index', ['tab' => 'profile'])
                ->withErrors(['code' => __('account.email_change_expired')], 'emailChange');
        }

        if (! hash_equals($user->email_change_code, $request->input('code'))) {
            return redirect()->route('account.index', ['tab' => 'profile'])
                ->withErrors(['code' => __('account.email_change_invalid_code')], 'emailChange');
        }

        $oldEmail = $user->email;
        $newEmail = $user->email_change_pending;

        $user->update([
            'email' => $newEmail,
            'email_verified_at' => null,
            'email_change_pending' => null,
            'email_change_code' => null,
            'email_change_expires_at' => null,
        ]);

        UserActivityLog::fromRequest($request, [
            'user_id' => $user->id,
            'event' => 'email_changed',
            'properties' => ['old_email' => $oldEmail, 'new_email' => $newEmail],
        ]);

        // Post system comment on latest active order
        $activeOrder = $user->orders()
            ->whereNotIn('status', ['cancelled', 'delivered', 'completed'])
            ->latest()
            ->first();

        if ($activeOrder) {
            OrderComment::create([
                'order_id' => $activeOrder->id,
                'user_id' => null,
                'body' => __('account.comment_email_changed_detail', [
                    'old' => $oldEmail,
                    'new' => $newEmail,
                ]),
                'is_internal' => true,
            ]);
        }

        return redirect()->route('account.index', ['tab' => 'profile'])
            ->with('status', 'email-changed');
    }

    public function cancelDeletion(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->deletion_requested) {
            return redirect()->route('account.index');
        }

        $user->update(['deletion_requested' => false]);

        UserActivityLog::fromRequest($request, [
            'user_id' => $user->id,
            'event' => 'deletion_cancelled',
        ]);

        return redirect()->route('account.index')->with('status', 'deletion-cancelled');
    }
}
