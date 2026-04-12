@extends('emails.layout')

{{-- Drop a 280×40px PNG at public/assets/email/foundation-activities.png to replace the text. --}}
@section('nameplate')
    <span style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                 font-size:10px;font-weight:700;letter-spacing:3px;
                 text-transform:uppercase;color:rgba(255,255,255,0.55);">
        Activities
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

    {{-- Activities header band --}}
    <tr>
        <td style="background-color:#edf7f0;padding:18px 40px;border-bottom:2px solid #1b4332;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <td>
                        <p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                                   font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;
                                   color:#1b4332;">
                            Dataphyte Foundation &mdash; Activities
                        </p>
                    </td>
                    <td style="text-align:right;">
                        <p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                                   font-size:12px;color:#555555;">{{ $sentDate }}</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- Subject --}}
    <tr>
        <td style="padding:28px 40px 12px;">
            <h1 style="margin:0;font-family:Georgia,'Times New Roman',serif;font-size:24px;
                        font-weight:700;line-height:1.35;color:#1b4332;">
                {{ $subject }}
            </h1>
            @if(!empty($author))
            <p style="margin:8px 0 0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                       font-size:13px;color:#888888;">By {{ $author }}</p>
            @endif
        </td>
    </tr>

    {{-- Rule --}}
    <tr>
        <td style="padding:0 40px 8px;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr><td style="border-top:1px solid #dddddd;font-size:0;line-height:0;">&nbsp;</td></tr>
            </table>
        </td>
    </tr>

    {{-- Body --}}
    <tr>
        <td class="content-padding"
            style="padding:20px 40px 32px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                   font-size:15px;line-height:1.75;color:#333333;">
            {!! $content !!}
        </td>
    </tr>

    {{-- Activities image gallery placeholder (editors insert via bard) --}}

    {{-- CTA --}}
    <tr>
        <td style="padding:0 40px 36px;text-align:center;">
            <a href="{{ $foundationUrl ?? 'https://foundation.dataphyte.org/activities' }}"
               style="display:inline-block;background-color:#2d6a4f;color:#ffffff;
                      font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:14px;
                      font-weight:600;text-decoration:none;padding:12px 28px;border-radius:3px;">
                See All Activities &rarr;
            </a>
        </td>
    </tr>

@endsection
