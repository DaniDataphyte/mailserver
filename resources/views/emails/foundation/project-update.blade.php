@extends('emails.layout')

{{-- Drop a 280×40px PNG at public/assets/email/foundation-project-update.png to replace the text. --}}
@section('nameplate')
    <span style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                 font-size:10px;font-weight:700;letter-spacing:3px;
                 text-transform:uppercase;color:rgba(255,255,255,0.55);">
        Project Update
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

    {{-- Project Update badge --}}
    <tr>
        <td style="padding:28px 40px 0;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <td style="background-color:#1b4332;padding:5px 14px;border-radius:2px;">
                        <span style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                                     font-size:10px;font-weight:700;letter-spacing:2px;
                                     text-transform:uppercase;color:#ffffff;">
                            Project Update
                        </span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- Project name / headline --}}
    <tr>
        <td style="padding:14px 40px 8px;">
            <h1 style="margin:0;font-family:Georgia,'Times New Roman',serif;font-size:26px;
                        font-weight:700;line-height:1.35;color:#1b4332;">
                {{ $subject }}
            </h1>
            @if(!empty($author))
            <p style="margin:8px 0 0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                       font-size:13px;color:#888888;">
                Reported by {{ $author }} &nbsp;&middot;&nbsp; {{ $sentDate ?? now()->format('F j, Y') }}
            </p>
            @endif
        </td>
    </tr>

    {{-- Divider --}}
    <tr>
        <td style="padding:14px 40px 0;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr><td style="border-top:1px solid #dddddd;font-size:0;line-height:0;">&nbsp;</td></tr>
            </table>
        </td>
    </tr>

    {{-- Body --}}
    <tr>
        <td class="content-padding"
            style="padding:20px 40px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                   font-size:15px;line-height:1.75;color:#333333;">
            {!! $content !!}
        </td>
    </tr>

    {{-- Impact highlight box --}}
    @if(!empty($impactHighlight))
    <tr>
        <td style="padding:0 40px 24px;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <td style="background-color:#edf7f0;border:1px solid #b7e4c7;
                               border-radius:3px;padding:18px 20px;">
                        <p style="margin:0 0 4px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                                   font-size:10px;font-weight:700;text-transform:uppercase;
                                   letter-spacing:1.5px;color:#1b4332;">Impact Highlight</p>
                        <p style="margin:0;font-family:Georgia,'Times New Roman',serif;
                                   font-size:15px;line-height:1.6;color:#1b4332;">
                            {{ $impactHighlight }}
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    @endif

    {{-- CTA --}}
    <tr>
        <td style="padding:0 40px 36px;text-align:center;">
            <a href="{{ $foundationUrl ?? 'https://foundation.dataphyte.org/projects' }}"
               style="display:inline-block;background-color:#1b4332;color:#ffffff;
                      font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:14px;
                      font-weight:600;text-decoration:none;padding:12px 28px;border-radius:3px;">
                View All Projects &rarr;
            </a>
        </td>
    </tr>

@endsection
