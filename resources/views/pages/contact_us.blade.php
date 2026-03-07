<x-app-layout :minimal-footer="true">
    @include('components.page-seo-slots', ['page' => $page])

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">

        {{-- Header --}}
        <div class="bg-white rounded-xl shadow-sm border-t-4 border-primary-500 p-6 mb-6 text-center">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">{{ $page->getTitle() }}</h1>
            <p class="text-gray-500">{{ __('contact.subtitle') }}</p>
        </div>

        @php
            $contactEmail = trim((string) \App\Models\Setting::get('contact_email', '')) ?: 'info@wasetzon.com';
            $whatsappRaw = preg_replace('/\D/', '', \App\Models\Setting::get('whatsapp', '966556063500'));
            $whatsappFormatted = $whatsappRaw ? '+' . $whatsappRaw : '+966556063500';
        @endphp

        {{-- Contact info (معلومات الإتصال) --}}
        <div class="bg-white border border-gray-200 rounded-xl p-6 mb-6 shadow-sm">
            <h2 class="text-lg font-bold text-gray-900 mb-4">{{ __('contact.info_title') }}</h2>
            <div class="space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                    <span class="text-sm font-semibold text-gray-500 sm:w-32 flex-shrink-0">{{ __('contact.email_label') }}</span>
                    <a href="mailto:{{ $contactEmail }}" class="text-primary-600 hover:underline font-medium">{{ $contactEmail }}</a>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                    <span class="text-sm font-semibold text-gray-500 sm:w-32 flex-shrink-0">{{ __('contact.whatsapp_label') }}</span>
                    <a href="https://wa.me/{{ $whatsappRaw }}" target="_blank" rel="noopener" class="text-primary-600 hover:underline font-medium">{{ $whatsappFormatted }}</a>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-start gap-2">
                    <span class="text-sm font-semibold text-gray-500 sm:w-32 flex-shrink-0">{{ __('contact.address_label') }}</span>
                    <span class="text-gray-700">{{ __('contact.address') }}</span>
                </div>
            </div>
        </div>

        {{-- Contact form --}}
        <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
            <h2 class="text-lg font-bold text-gray-900 mb-4">{{ __('contact.form_title') }}</h2>

            @if (session('status') === 'contact-sent')
                <div class="mb-4 p-4 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm">
                    {{ __('contact.success_message') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 p-4 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('contact.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="contact-name" class="block text-sm font-semibold text-gray-700 mb-1">{{ __('contact.name_label') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="contact-name" value="{{ old('name', auth()->user()?->name ?? '') }}" required
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-400 focus:outline-none transition"
                        placeholder="{{ __('contact.name_placeholder') }}">
                </div>
                <div>
                    <label for="contact-email" class="block text-sm font-semibold text-gray-700 mb-1">{{ __('contact.email_label') }} <span class="text-red-500">*</span></label>
                    <input type="email" name="email" id="contact-email" value="{{ old('email', auth()->user()?->email ?? '') }}" required
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-400 focus:outline-none transition"
                        placeholder="{{ __('contact.email_placeholder') }}">
                </div>
                <div>
                    <label for="contact-phone" class="block text-sm font-semibold text-gray-700 mb-1">{{ __('contact.phone_label') }}</label>
                    <input type="tel" name="phone" id="contact-phone" value="{{ old('phone', auth()->user()?->phone ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-400 focus:outline-none transition"
                        placeholder="{{ __('contact.phone_placeholder') }}">
                </div>
                <div>
                    <label for="contact-subject" class="block text-sm font-semibold text-gray-700 mb-1">{{ __('contact.subject_label') }}</label>
                    <input type="text" name="subject" id="contact-subject" value="{{ old('subject') }}"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-400 focus:outline-none transition"
                        placeholder="{{ __('contact.subject_placeholder') }}">
                </div>
                <div>
                    <label for="contact-message" class="block text-sm font-semibold text-gray-700 mb-1">{{ __('contact.message_label') }} <span class="text-red-500">*</span></label>
                    <textarea name="message" id="contact-message" rows="5" required
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-400 focus:outline-none transition resize-y"
                        placeholder="{{ __('contact.message_placeholder') }}">{{ old('message') }}</textarea>
                </div>
                <div>
                    <button type="submit" class="px-5 py-2.5 bg-primary-600 text-white text-sm font-semibold rounded-lg hover:bg-primary-700 transition">
                        {{ __('contact.submit') }}
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-app-layout>
