<?php

namespace App\Livewire;

use App\Models\Activity;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PaymentNotificationForm extends Component
{
    public string $amount = '';
    public string $payment_method = '';
    public string $order_number = '';
    public string $notes = '';
    public string $guest_name = '';
    public string $guest_phone = '';
    public bool $submitted = false;

    protected function rules(): array
    {
        $rules = [
            'amount'         => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'string'],
            'order_number'   => ['nullable', 'string'],
            'notes'          => ['nullable', 'string', 'max:1000'],
        ];

        if (! Auth::check()) {
            $rules['guest_name']  = ['required', 'string', 'max:100'];
            $rules['guest_phone'] = ['required', 'string', 'max:30'];
        }

        return $rules;
    }

    public function submit(): void
    {
        $this->validate();

        $user = Auth::user();

        $order = null;
        if ($this->order_number && is_numeric($this->order_number) && $user) {
            $order = Order::where('id', $this->order_number)
                ->where('user_id', $user->id)
                ->first();
        }

        $data = [
            'amount'         => $this->amount,
            'payment_method' => $this->payment_method,
            'order_number'   => $this->order_number,
            'notes'          => $this->notes,
        ];

        if ($user) {
            $data['user_name']  = $user->name;
            $data['user_email'] = $user->email;
        } else {
            $data['guest_name']  = $this->guest_name;
            $data['guest_phone'] = $this->guest_phone;
        }

        Activity::create([
            'type'         => 'payment_notification',
            'subject_type' => $order ? Order::class : null,
            'subject_id'   => $order?->id,
            'causer_id'    => $user?->id,
            'data'         => $data,
            'created_at'   => now(),
        ]);

        $this->reset(['amount', 'payment_method', 'order_number', 'notes', 'guest_name', 'guest_phone']);
        $this->submitted = true;
    }

    public function render()
    {
        $userOrders = collect();

        if (Auth::check()) {
            $userOrders = Order::where('user_id', Auth::id())
                ->latest()
                ->take(10)
                ->get(['id', 'total_amount']);
        }

        return view('livewire.payment-notification-form', [
            'userOrders' => $userOrders,
            'isGuest'    => ! Auth::check(),
        ]);
    }
}
