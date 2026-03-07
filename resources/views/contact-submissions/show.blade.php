<x-app-layout :minimal-footer="true">

<div class="max-w-2xl mx-auto px-4 py-6 sm:py-8">
    <div class="mb-6">
        <a href="{{ route('inbox.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-primary-600 hover:text-primary-700 transition-colors">
            <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            {{ __('inbox.inbox') }}
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 bg-gray-50">
            <h1 class="text-lg font-bold text-gray-900">{{ __('contact.submission_title') }}</h1>
            <p class="text-xs text-gray-500 mt-0.5">{{ $contactSubmission->created_at->translatedFormat('l، d F Y - H:i') }}</p>
        </div>

        <div class="p-5 space-y-4">
            <div>
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('contact.name_label') }}</span>
                <p class="text-gray-900 font-medium mt-0.5">{{ $contactSubmission->name }}</p>
            </div>
            <div>
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('contact.email_label') }}</span>
                <p class="mt-0.5">
                    <a href="mailto:{{ $contactSubmission->email }}" class="text-primary-600 hover:underline font-medium">{{ $contactSubmission->email }}</a>
                </p>
            </div>
            @if ($contactSubmission->phone)
                <div>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('contact.phone_label') }}</span>
                    <p class="text-gray-900 font-medium mt-0.5">{{ $contactSubmission->phone }}</p>
                </div>
            @endif
            @if ($contactSubmission->subject)
                <div>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('contact.subject_label') }}</span>
                    <p class="text-gray-900 font-medium mt-0.5">{{ $contactSubmission->subject }}</p>
                </div>
            @endif
            <div>
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('contact.message_label') }}</span>
                <div class="mt-0.5 p-4 bg-gray-50 rounded-lg text-gray-800 whitespace-pre-wrap">{{ $contactSubmission->message }}</div>
            </div>
            @if ($contactSubmission->user)
                <div class="pt-3 border-t border-gray-100">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('contact.user_label') }}</span>
                    <p class="text-gray-600 text-sm mt-0.5">
                        <a href="{{ route('orders.index') }}?search={{ urlencode($contactSubmission->user->email) }}" class="text-primary-600 hover:underline">
                            {{ $contactSubmission->user->name }} ({{ $contactSubmission->user->email }})
                        </a>
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>

</x-app-layout>
