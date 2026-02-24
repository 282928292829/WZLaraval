<x-emails.layout :subject="__('email.welcome.subject')">

    <p class="greeting">{{ __('email.welcome.greeting') }} {{ $user->name }} 🎉</p>

    <p class="intro">
        {{ __('email.welcome.intro') }}
    </p>

    {{-- What you can do --}}
    <div class="card">
        <div class="card-title">{{ __('email.welcome.what_title') }}</div>
        <table>
            <tbody>
                <tr>
                    <td style="width:32px;font-size:20px;">🛒</td>
                    <td>
                        <strong>{{ __('email.welcome.action1_title') }}</strong>
                        <div style="font-size:12px;color:#6b7280;margin-top:2px;">{{ __('email.welcome.action1_desc') }}</div>
                    </td>
                </tr>
                <tr>
                    <td style="font-size:20px;">📦</td>
                    <td>
                        <strong>{{ __('email.welcome.action2_title') }}</strong>
                        <div style="font-size:12px;color:#6b7280;margin-top:2px;">{{ __('email.welcome.action2_desc') }}</div>
                    </td>
                </tr>
                <tr>
                    <td style="font-size:20px;">💬</td>
                    <td>
                        <strong>{{ __('email.welcome.action3_title') }}</strong>
                        <div style="font-size:12px;color:#6b7280;margin-top:2px;">{{ __('email.welcome.action3_desc') }}</div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Account info --}}
    <div class="card">
        <div class="card-title">{{ __('email.welcome.account_info') }}</div>
        <div class="info-row">
            <span class="info-label">{{ __('email.welcome.name') }}</span>
            <span class="info-value">{{ $user->name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">{{ __('email.welcome.email') }}</span>
            <span class="info-value">{{ $user->email }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">{{ __('email.welcome.joined') }}</span>
            <span class="info-value">{{ $user->created_at->format('Y/m/d') }}</span>
        </div>
    </div>

    {{-- CTA --}}
    <div style="text-align:center;margin:28px 0;">
        <a href="{{ url('/new-order') }}" class="btn">
            {{ __('email.welcome.cta') }}
        </a>
    </div>

    <hr class="divider">

    <p style="font-size:13px;color:#6b7280;line-height:1.7;">
        {{ __('email.welcome.footer') }}
        <a href="{{ url('/') }}" style="color:#f97316;">{{ __('email.welcome.contact_us') }}</a>.
    </p>

</x-emails.layout>
