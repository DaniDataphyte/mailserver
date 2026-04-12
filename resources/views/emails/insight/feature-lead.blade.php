@extends('emails.layout')

{{-- Product nameplate — identifies this template in the header band.
     Drop a 280×40px PNG at public/assets/email/insight-feature-lead.png to replace the text. --}}
@section('nameplate')
    <span style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                 font-size:10px;font-weight:700;letter-spacing:3px;
                 text-transform:uppercase;color:rgba(255,255,255,0.55);">
        Feature Lead
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

    {{-- Issue label --}}
    <tr>
        <td style="padding:32px 40px 0;text-align:center;">
            <p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                       font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;
                       color:#c0392b;">Dataphyte Insight</p>
        </td>
    </tr>

    {{-- Headline --}}
    <tr>
        <td style="padding:16px 40px 8px;text-align:center;">
            <h1 style="margin:0;font-family:Georgia,'Times New Roman',serif;font-size:32px;
                        font-weight:700;line-height:1.25;color:#0d1b2a;">
                {{ $subject }}
            </h1>
        </td>
    </tr>

    {{-- Meta: date + author --}}
    <tr>
        <td style="padding:12px 40px 28px;text-align:center;">
            <p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                       font-size:13px;color:#888888;">
                {{ $sentDate }}
                @if(!empty($author))
                &nbsp;&middot;&nbsp; By {{ $author }}
                @endif
            </p>
        </td>
    </tr>

    {{-- Divider --}}
    <tr>
        <td style="padding:0 40px;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr><td style="border-top:2px solid #0d1b2a;font-size:0;line-height:0;">&nbsp;</td></tr>
            </table>
        </td>
    </tr>

    {{-- Body content --}}
    <tr>
        <td class="content-padding"
            style="padding:32px 40px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                   font-size:16px;line-height:1.75;color:#333333;">
            {!! $content !!}
        </td>
    </tr>

    {{-- CTA if provided --}}
    @if(!empty($ctaUrl) && !empty($ctaLabel))
    <tr>
        <td style="padding:0 40px 40px;text-align:center;">
            <a href="{{ $ctaUrl }}"
               style="display:inline-block;background-color:#c0392b;color:#ffffff;
                      font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:15px;
                      font-weight:600;text-decoration:none;padding:14px 36px;border-radius:3px;">
                {{ $ctaLabel }}
            </a>
        </td>
    </tr>
    @endif

@endsection
