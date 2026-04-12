@extends('emails.layout')

{{-- Drop a 280×40px PNG at public/assets/email/insight-single-story.png to replace the text. --}}
@section('nameplate')
    <span style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                 font-size:10px;font-weight:700;letter-spacing:3px;
                 text-transform:uppercase;color:rgba(255,255,255,0.55);">
        Single Story
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

    {{-- Kicker --}}
    <tr>
        <td style="padding:32px 40px 0;text-align:left;">
            <p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                       font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;
                       color:#c0392b;">Dataphyte Insight</p>
        </td>
    </tr>

    {{-- Headline --}}
    <tr>
        <td style="padding:14px 40px 10px;text-align:left;">
            <h1 style="margin:0;font-family:Georgia,'Times New Roman',serif;font-size:30px;
                        font-weight:700;line-height:1.3;color:#0d1b2a;">
                {{ $subject }}
            </h1>
        </td>
    </tr>

    {{-- Byline --}}
    @if(!empty($author))
    <tr>
        <td style="padding:0 40px 20px;">
            <p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                       font-size:13px;color:#888888;">
                By <strong style="color:#555555;">{{ $author }}</strong>
                &nbsp;&middot;&nbsp; {{ $sentDate ?? now()->format('F j, Y') }}
            </p>
        </td>
    </tr>
    @endif

    {{-- Body --}}
    <tr>
        <td class="content-padding"
            style="padding:24px 40px;font-family:Georgia,'Times New Roman',serif;
                   font-size:17px;line-height:1.8;color:#222222;">
            {!! $content !!}
        </td>
    </tr>

    {{-- Share / read online nudge --}}
    <tr>
        <td style="padding:0 40px 36px;text-align:center;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <td style="border-top:1px solid #eeeeee;padding-top:24px;text-align:center;">
                        <p style="margin:0 0 4px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                                   font-size:13px;color:#888888;">
                            Enjoying this story?
                        </p>
                        <a href="{{ $webViewUrl ?? '#' }}"
                           style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:13px;
                                  color:#c0392b;text-decoration:underline;">
                            Read it in your browser
                        </a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

@endsection
