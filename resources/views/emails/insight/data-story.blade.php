@extends('emails.layout')

{{-- Drop a 280×40px PNG at public/assets/email/insight-data-story.png to replace the text. --}}
@section('nameplate')
    <span style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                 font-size:10px;font-weight:700;letter-spacing:3px;
                 text-transform:uppercase;color:rgba(255,255,255,0.55);">
        Data Story
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

    {{-- Data story label --}}
    <tr>
        <td style="padding:28px 40px 0;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <td style="background-color:#0d1b2a;padding:4px 12px;">
                        <span style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                                     font-size:10px;font-weight:700;letter-spacing:2px;
                                     text-transform:uppercase;color:#ffffff;">
                            Data Story
                        </span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- Headline --}}
    <tr>
        <td style="padding:14px 40px 8px;">
            <h1 style="margin:0;font-family:Georgia,'Times New Roman',serif;font-size:30px;
                        font-weight:700;line-height:1.25;color:#0d1b2a;">
                {{ $subject }}
            </h1>
        </td>
    </tr>

    {{-- Byline + date --}}
    <tr>
        <td style="padding:0 40px 20px;">
            <p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                       font-size:13px;color:#888888;border-bottom:1px solid #eeeeee;padding-bottom:16px;">
                @if(!empty($author))
                    Analysis by <strong style="color:#555555;">{{ $author }}</strong> &nbsp;&middot;&nbsp;
                @endif
                {{ $sentDate ?? now()->format('F j, Y') }}
            </p>
        </td>
    </tr>

    {{-- Content — Bard HTML. Editors use bard image blocks for charts/infographics --}}
    <tr>
        <td class="content-padding"
            style="padding:4px 40px 28px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                   font-size:16px;line-height:1.8;color:#2c2c2c;">
            {!! $content !!}
        </td>
    </tr>

    {{-- Key takeaway box --}}
    @if(!empty($keyTakeaway))
    <tr>
        <td style="padding:0 40px 28px;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <td style="background-color:#f0f4f8;border-left:4px solid #0d1b2a;padding:16px 20px;">
                        <p style="margin:0 0 4px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                                   font-size:10px;font-weight:700;text-transform:uppercase;
                                   letter-spacing:1.5px;color:#0d1b2a;">Key Takeaway</p>
                        <p style="margin:0;font-family:Georgia,'Times New Roman',serif;
                                   font-size:15px;line-height:1.6;color:#333333;">
                            {{ $keyTakeaway }}
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    @endif

    {{-- Methodology / data source note --}}
    <tr>
        <td style="padding:0 40px 36px;">
            <p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                       font-size:11px;line-height:1.6;color:#aaaaaa;
                       border-top:1px solid #eeeeee;padding-top:16px;">
                Data sourced and analysed by the Dataphyte Insight team.
                Methodology available on request.
            </p>
        </td>
    </tr>

@endsection
