@extends('emails.layout')

{{-- Drop a 280×40px PNG at public/assets/email/insight-breaking.png to replace the text. --}}
@section('nameplate')
    <span style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                 font-size:10px;font-weight:700;letter-spacing:3px;
                 text-transform:uppercase;color:rgba(255,255,255,0.55);">
        Breaking News
    </span>
@endsection

@section('content')

    {{-- Breaking alert band --}}
    <tr>
        <td style="background-color:#c0392b;padding:10px 40px;text-align:center;">
            <span style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                         font-size:11px;font-weight:700;letter-spacing:3px;
                         text-transform:uppercase;color:#ffffff;">
                &#9679; Breaking News
            </span>
        </td>
    </tr>

    {{-- Hero image --}}
    @if(!empty($heroImageUrl))
    <tr>
        <td class="hero-image" style="padding:0;">
            <img src="{{ $heroImageUrl }}" alt="" width="600"
                 style="width:100%;max-width:600px;height:auto;display:block;">
        </td>
    </tr>
    @endif

    {{-- Timestamp --}}
    <tr>
        <td style="padding:28px 40px 0;text-align:left;">
            <p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                       font-size:12px;font-weight:700;color:#c0392b;letter-spacing:1px;
                       text-transform:uppercase;">
                {{ $sentDate }}
                @if(!empty($author))&nbsp;&middot;&nbsp; {{ $author }}@endif
            </p>
        </td>
    </tr>

    {{-- Headline --}}
    <tr>
        <td style="padding:10px 40px 8px;">
            <h1 style="margin:0;font-family:Georgia,'Times New Roman',serif;font-size:34px;
                        font-weight:700;line-height:1.2;color:#0d1b2a;letter-spacing:-0.5px;">
                {{ $subject }}
            </h1>
        </td>
    </tr>

    {{-- Rule --}}
    <tr>
        <td style="padding:16px 40px 0;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <td width="48" style="border-top:3px solid #c0392b;font-size:0;line-height:0;">&nbsp;</td>
                    <td style="border-top:1px solid #eeeeee;font-size:0;line-height:0;">&nbsp;</td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- Body --}}
    <tr>
        <td class="content-padding"
            style="padding:24px 40px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                   font-size:16px;line-height:1.75;color:#222222;">
            {!! $content !!}
        </td>
    </tr>

    {{-- Read more CTA --}}
    @if(!empty($ctaUrl))
    <tr>
        <td style="padding:0 40px 36px;text-align:left;">
            <a href="{{ $ctaUrl }}"
               style="display:inline-block;background-color:#c0392b;color:#ffffff;
                      font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:14px;
                      font-weight:700;text-decoration:none;padding:12px 28px;border-radius:2px;
                      text-transform:uppercase;letter-spacing:0.5px;">
                Read Full Story &rarr;
            </a>
        </td>
    </tr>
    @endif

@endsection
