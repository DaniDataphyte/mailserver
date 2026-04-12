@extends('emails.layout')

{{-- Drop a 280×40px PNG at public/assets/email/insight-roundup.png to replace the text. --}}
@section('nameplate')
    <span style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                 font-size:10px;font-weight:700;letter-spacing:3px;
                 text-transform:uppercase;color:rgba(255,255,255,0.55);">
        Newsroom Roundup
    </span>
@endsection

@section('content')

    {{-- Edition header --}}
    <tr>
        <td style="background-color:#f7f7f7;border-bottom:2px solid #0d1b2a;padding:16px 40px;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <td>
                        <p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                                   font-size:11px;font-weight:700;letter-spacing:2px;
                                   text-transform:uppercase;color:#0d1b2a;">
                            Newsroom Roundup
                        </p>
                    </td>
                    <td style="text-align:right;">
                        <p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                                   font-size:12px;color:#888888;">{{ $sentDate }}</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- Edition headline --}}
    <tr>
        <td style="padding:28px 40px 20px;text-align:center;">
            <h1 style="margin:0;font-family:Georgia,'Times New Roman',serif;font-size:26px;
                        font-weight:700;line-height:1.3;color:#0d1b2a;">
                {{ $subject }}
            </h1>
        </td>
    </tr>

    {{-- Body — editors use bard headings (h2/h3) to separate story sections --}}
    <tr>
        <td class="content-padding"
            style="padding:0 40px 12px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                   font-size:15px;line-height:1.75;color:#333333;">

            {{-- Bard renders h2 per story section. Style overrides via inline CSS in bard output --}}
            <style>
                /* Email-client-safe section heading treatment */
                .roundup-content h2 {
                    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                    font-size: 13px;
                    font-weight: 700;
                    letter-spacing: 1.5px;
                    text-transform: uppercase;
                    color: #c0392b;
                    margin: 28px 0 6px;
                    padding-bottom: 6px;
                    border-bottom: 1px solid #eeeeee;
                }
                .roundup-content h3 {
                    font-family: Georgia, 'Times New Roman', serif;
                    font-size: 19px;
                    font-weight: 700;
                    color: #0d1b2a;
                    margin: 4px 0 10px;
                    line-height: 1.3;
                }
                .roundup-content p { margin: 0 0 14px; }
                .roundup-content a { color: #c0392b; }
            </style>

            <div class="roundup-content">
                {!! $content !!}
            </div>
        </td>
    </tr>

    {{-- Footer CTA --}}
    <tr>
        <td style="padding:8px 40px 36px;text-align:center;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <td style="border-top:1px solid #dddddd;padding-top:20px;text-align:center;">
                        <a href="{{ $moreStoriesUrl ?? 'https://insight.dataphyte.com' }}"
                           style="display:inline-block;border:2px solid #0d1b2a;color:#0d1b2a;
                                  font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:13px;
                                  font-weight:700;text-decoration:none;padding:10px 24px;border-radius:2px;
                                  text-transform:uppercase;letter-spacing:0.5px;">
                            More Stories &rarr;
                        </a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

@endsection
