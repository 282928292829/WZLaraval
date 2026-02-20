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
    public bool $submitted = false;

    protected function rules(): array
    {
        return [
            'amount'         => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'string'],
            'order_number'   => ['nullable', 'string'],
            'notes'          => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function submit(): void
    {
        $this->validate();

        $user = Auth::user();

        $order = null;
        if ($this->order_number && is_numeric($this->order_number)) {
            $order = Order::where('id', $this->order_number)
                ->where('user_id', $user->id)
                ->first();
        }

        Activity::create([
            'type'         => 'payment_notification',
            'subject_type' => $order ? Order::class : null,
            'subject_id'   => $order?->id,
            'causer_id'    => $user->id,
            'data'         => [
                'amount'         => $this->amount,
                'payment_method' => $this->payment_method,
                'order_number'   => $this->order_number,
                'notes'          => $this->notes,
                'user_name'      => $user->name,
                'user_email'     => $user->email,
            ],
            'created_at'   => now(),
        ]);

        $this->reset(['amount', 'payment_method', 'order_number', 'notes']);
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

        return view('livewire.payment-notification-form', compact('userOrders'));
    }
}
