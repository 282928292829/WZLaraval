<?php

namespace App\Livewire;

use App\Models\Activity;
use App\Models\ActivityFile;
use App\Models\Order;
use App\Models\Setting;
use App\Services\ImageConversionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PaymentNotificationForm extends Component
{
    use \Livewire\WithFileUploads;

    public string $amount = '';

    public string $payment_method = '';

    public string $order_number = '';

    public string $notes = '';

    public string $guest_name = '';

    public string $guest_phone = '';

    /** @var array<\Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $receipts = [];

    public bool $submitted = false;

    protected function rules(): array
    {
        $maxFiles = max(0, (int) Setting::get('payment_notify_standalone_max_files', 5));
        $maxFileKb = max(1, (int) Setting::get('comment_max_file_size_mb', 10)) * 1024;

        $rules = [
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'string'],
            'order_number' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        if (! Auth::check()) {
            $rules['guest_name'] = ['required', 'string', 'max:100'];
            $rules['guest_phone'] = ['required', 'string', 'max:30'];
        }

        if ($maxFiles > 0) {
            $rules['receipts'] = ['nullable', 'array', 'max:50'];
            $rules['receipts.*'] = ['file', 'max:'.$maxFileKb, 'mimes:'.allowed_upload_mimes()];
        }

        return $rules;
    }

    public function submit(): void
    {
        $this->amount = (string) to_english_digits($this->amount);

        $maxFiles = max(0, (int) Setting::get('payment_notify_standalone_max_files', 5));
        if ($maxFiles > 0 && count($this->receipts) > $maxFiles) {
            $this->receipts = array_slice($this->receipts, 0, $maxFiles);
            session()->flash('payment_notify_max_exceeded', __('payment_notify.max_files_exceeded'));
        }

        $this->validate();

        $user = Auth::user();

        $order = null;
        if ($this->order_number && $user) {
            $order = is_numeric($this->order_number)
                ? Order::where('id', $this->order_number)->where('user_id', $user->id)->first()
                : Order::where('order_number', $this->order_number)->where('user_id', $user->id)->first();
        }

        $bankLabel = $this->resolvePaymentMethodLabel($this->payment_method);

        $data = [
            'amount' => $this->amount,
            'payment_method' => $this->payment_method,
            'payment_method_label' => $bankLabel,
            'order_number' => $this->order_number,
            'notes' => $this->notes,
        ];

        if ($user) {
            $data['user_name'] = $user->name;
            $data['user_email'] = $user->email;
        } else {
            $data['guest_name'] = $this->guest_name;
            $data['guest_phone'] = $this->guest_phone;
        }

        $activity = Activity::create([
            'type' => 'payment_notification',
            'subject_type' => $order ? Order::class : null,
            'subject_id' => $order?->id,
            'causer_id' => $user?->id,
            'data' => $data,
            'created_at' => now(),
        ]);

        $imageService = app(ImageConversionService::class);

        if ($order && count($this->receipts) > 0) {
            $body = __('orders.payment_notify_comment', [
                'amount' => $this->amount,
                'bank' => $bankLabel,
            ])."\n".__('orders.payment_notify_from_bank_page');
            if ($this->notes) {
                $body .= "\n".__('orders.payment_notify_notes').': '.$this->notes;
            }
            $comment = $order->comments()->create([
                'user_id' => $user?->id ?? $order->user_id,
                'body' => $body,
                'is_internal' => false,
            ]);
            foreach ($this->receipts as $file) {
                $stored = $imageService->storeForDisplay($file, 'order-files/'.$order->id, 'public');
                $order->files()->create([
                    'user_id' => $user?->id ?? $order->user_id,
                    'comment_id' => $comment->id,
                    'path' => $stored['path'],
                    'original_name' => $stored['original_name'],
                    'mime_type' => $stored['mime_type'],
                    'size' => $stored['size'],
                    'type' => 'receipt',
                ]);
            }
        } elseif (count($this->receipts) > 0) {
            foreach ($this->receipts as $file) {
                $stored = $imageService->storeForDisplay($file, 'payment-notifications/'.$activity->id, 'public');
                ActivityFile::create([
                    'activity_id' => $activity->id,
                    'path' => $stored['path'],
                    'original_name' => $stored['original_name'],
                    'mime_type' => $stored['mime_type'],
                    'size' => $stored['size'],
                ]);
            }
        }

        $this->reset(['amount', 'payment_method', 'order_number', 'notes', 'guest_name', 'guest_phone', 'receipts']);
        $this->submitted = true;
    }

    private function resolvePaymentMethodLabel(string $method): string
    {
        $banks = ['alrajhi', 'alahli', 'albilad', 'alinma', 'samba', 'saib', 'riyad', 'aljazeera', 'alfransi', 'arabi', 'stc_pay', 'mada'];
        if (in_array($method, $banks, true)) {
            return __('orders.banks.'.$method);
        }

        return match ($method) {
            'visa_mastercard' => __('orders.payment_method_visa_mastercard'),
            'international_bank_transfer' => __('orders.payment_method_international_bank_transfer'),
            'credit_card' => __('orders.payment_method_credit_card'),
            'paypal' => __('orders.payment_method_paypal'),
            'other' => __('orders.payment_method_other'),
            default => $method,
        };
    }

    public function render()
    {
        $userOrders = collect();

        if (Auth::check()) {
            $userOrders = Order::where('user_id', Auth::id())
                ->latest()
                ->take(10)
                ->get(['id', 'order_number', 'total_amount']);
        }

        $maxStandaloneFiles = max(0, (int) Setting::get('payment_notify_standalone_max_files', 5));

        return view('livewire.payment-notification-form', [
            'userOrders' => $userOrders,
            'isGuest' => ! Auth::check(),
            'maxStandaloneFiles' => $maxStandaloneFiles,
        ]);
    }
}
