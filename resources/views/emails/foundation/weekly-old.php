@extends('emails.layout')

{{-- Drop a 280×40px PNG at public/assets/email/foundation-weekly.png to replace the text. --}}
@section('nameplate')
    <span style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                 font-size:10px;font-weight:700;letter-spacing:3px;
                 text-transform:uppercase;color:rgba(255,255,255,0.55);">
        Weekly Update
    </span>
@endsection

@section('content')
    @php
        $brandColor = $headerColor ?? ($newsletterSettings['foundation_brand_color'] ?? '#1b4332');
    @endphp

    {{-- Hero image --}}
    @if(!empty($heroImageUrl))
    <tr>
        <td class="hero-image" style="padding:0;">
            <img src="{{ $heroImageUrl }}"
                 alt="{{ $subject }}"
                 width="600"
                 style="width:100%;max-width:600px;height:auto;display:block;">
        </td>
    </tr>
    @endif

    {{-- Intro band --}}
    <tr>
        <td style="background-color:#f6f8f7;padding:20px 40px;border-bottom:1px solid {{ $brandColor }};">
            <p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                       font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;
                       color:{{ $brandColor }};">
                Dataphyte Foundation &mdash; Weekly Update
            </p>
            <p style="margin:6px 0 0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                       font-size:13px;color:#555555;">{{ $sentDate ?? now()->format('F j, Y') }}</p>
        </td>
    </tr>

    {{-- Subject / headline --}}
    <tr>
        <td style="padding:24px 40px 16px;">
            <h1 style="margin:0;font-family:Georgia,'Times New Roman',serif;font-size:18px;
                        font-weight:700;line-height:1.3;color:{{ $brandColor }};">
                {{ $subject }}
            </h1>
            @if(!empty($author))
            <p style="margin:10px 0 0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                       font-size:13px;color:#888888;">
                By {{ $author }}
            </p>
            @endif
        </td>
    </tr>

    {{-- Rule --}}
    <tr>
        <td style="padding:0 40px;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr><td style="border-top:1px solid #dddddd;font-size:0;line-height:0;">&nbsp;</td></tr>
            </table>
        </td>
    </tr>

    {{-- Body --}}
    <tr>
        <td class="content-padding"
            style="padding:24px 40px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                   font-size:15px;line-height:1.75;color:#333333;">
            {!! $content !!}
        </td>
    </tr>

    {{-- CTA --}}
    <tr>
        <td style="padding:0 40px 40px;text-align:center;">
            <a href="{{ $foundationUrl ?? 'https://foundation.dataphyte.org' }}"
               style="display:inline-block;background-color:{{ $brandColor }};color:#ffffff;
                      font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:14px;
                      font-weight:600;text-decoration:none;padding:12px 28px;border-radius:3px;">
                Visit Dataphyte Foundation &rarr;
            </a>
        </td>
    </tr>

@endsection
