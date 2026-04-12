@extends('emails.layout')

{{-- Drop a 280×40px PNG at public/assets/email/insight-digest.png to replace the text. --}}
@section('nameplate')
    <span style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                 font-size:10px;font-weight:700;letter-spacing:3px;
                 text-transform:uppercase;color:rgba(255,255,255,0.55);">
        The Digest
    </span>
@endsection

@section('content')

    {{-- Hero image --}}
    @if(!empty($heroImageUrl))
    <tr>
        <td class="hero-image" style="padding:0;">
            <img src="{{ $heroImageUrl }}" alt="" width="600"
                 style="width:100%;max-width:600px;height:auto;display:block;">
        </td>
    </tr>
    @endif

    {{-- Digest label + subject --}}
    <tr>
        <td style="padding:28px 40px 0;text-align:center;">
            <p style="margin:0 0 10px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                       font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#c0392b;">
                Weekly Digest
            </p>
            <h1 style="margin:0;font-family:Georgia,'Times New Roman',serif;font-size:26px;
                        font-weight:700;line-height:1.3;color:#0d1b2a;">
                {{ $subject }}
            </h1>
            <p style="margin:10px 0 0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                       font-size:13px;color:#888888;">{{ $sentDate ?? now()->format('F j, Y') }}</p>
        </td>
    </tr>

    {{-- Top rule --}}
    <tr>
        <td style="padding:20px 40px 0;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr><td style="border-top:1px solid #dddddd;font-size:0;line-height:0;">&nbsp;</td></tr>
            </table>
        </td>
    </tr>

    {{-- Digest body — bard HTML renders article list --}}
    <tr>
        <td class="content-padding"
            style="padding:24px 40px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                   font-size:15px;line-height:1.7;color:#333333;">
            {!! $content !!}
        </td>
    </tr>

    {{-- Footer rule --}}
    <tr>
        <td style="padding:0 40px;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr><td style="border-top:1px solid #dddddd;font-size:0;line-height:0;">&nbsp;</td></tr>
            </table>
        </td>
    </tr>

    {{-- More stories CTA --}}
    <tr>
        <td style="padding:24px 40px 36px;text-align:center;">
            <a href="{{ $moreStoriesUrl ?? 'https://insight.dataphyte.com' }}"
               style="display:inline-block;background-color:#0d1b2a;color:#ffffff;
                      font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:14px;
                      font-weight:600;text-decoration:none;padding:12px 30px;border-radius:3px;">
                Read more on Dataphyte Insight &rarr;
            </a>
        </td>
    </tr>

@endsection
