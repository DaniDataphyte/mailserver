@extends('emails.layout')

{{-- Drop a 280×40px PNG at public/assets/email/foundation-weekly.png to replace the text. --}}
@section('nameplate')
    <span style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                 font-size:10px;font-weight:700;letter-spacing:3px;
                 text-transform:uppercase;color:rgba(255,255,255,0.55);">
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
            <a href="{{ $foundationUrl ?? 'https://dataphyte.org' }}"
               style="display:inline-block;background-color:{{ $brandColor }};color:#ffffff;
                      font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:14px;
                      font-weight:600;text-decoration:none;padding:12px 28px;border-radius:3px;">
                Visit Dataphyte Foundation &rarr;
            </a>
        </td>
    </tr>

@endsection
