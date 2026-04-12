<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $subject ?? '' }}</title>
    <!--[if mso]>
    <noscript>
        <xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></noscript>
    <![endif]-->
    <style>
        * { box-sizing: border-box; }
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        body { margin: 0 !important; padding: 0 !important; background-color: #f4f4f4; }
        a[x-apple-data-detectors] { color: inherit !important; text-decoration: none !important; }
        @media only screen and (max-width: 600px) {
            .email-container { width: 100% !important; }
            .stack-column, .stack-column-center { display: block !important; width: 100% !important; max-width: 100% !important; }
            .stack-column-center { text-align: center !important; }
            .hide-mobile { display: none !important; max-height: 0; overflow: hidden; }
            .hero-image img { height: auto !important; max-width: 100% !important; }
            .content-padding { padding: 20px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f4;">

    {{-- Preheader (hidden in inbox preview) --}}
    @if(!empty($preheader))
    <div style="display:none;font-size:1px;color:#f4f4f4;line-height:1px;max-height:0px;max-width:0px;opacity:0;overflow:hidden;">
        {{ $preheader }}&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;
    </div>
    @endif

    {{-- Email wrapper --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color:#f4f4f4;">
        <tr>
            <td style="padding:20px 0;">

                {{-- Email container --}}
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600"
                       class="email-container"
                       style="margin:0 auto;background-color:#ffffff;border-radius:4px;overflow:hidden;">

                    {{-- ============================================================ --}}
                    {{-- HEADER BAND                                                   --}}
                    {{-- Upper: collection logo (editor-controlled via GlobalSet)     --}}
                    {{-- Lower: product/template nameplate (hardcoded per template)   --}}
                    {{-- ============================================================ --}}
                    <tr>
                        <td style="background-color:{{ $headerColor ?? '#1a1a2e' }};padding:20px 40px 0;text-align:center;">

                            {{-- Collection logo (from newsletter_settings GlobalSet) --}}
                            @if(!empty($collectionLogo))
                                <img src="{{ $collectionLogo }}"
                                     alt="{{ $fromName ?? config('app.name') }}"
                                     height="36"
                                     style="height:36px;max-width:180px;display:inline-block;">
                            @else
                                <span style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                                             font-size:20px;font-weight:700;color:#ffffff;letter-spacing:-0.5px;">
                                    {{ $fromName ?? config('app.name') }}
                                </span>
                            @endif

                        </td>
                    </tr>

                    {{-- Product/template nameplate — injected by each child template --}}
                    @hasSection('nameplate')
                    <tr>
                        <td style="background-color:{{ $headerColor ?? '#0d1b2a' }};padding:0 40px 20px;text-align:center;">
                            @yield('nameplate')
                        </td>
                    </tr>
                    @endif
                    {{-- /nameplate --}}

                    {{-- ============================================================ --}}
                    {{-- MAIN CONTENT                                                  --}}
                    {{-- ============================================================ --}}
                    @yield('content')

                    {{-- ============================================================ --}}
                    {{-- FOOTER                                                        --}}
                    {{-- ============================================================ --}}
                    <tr>
                        <td style="background-color:#f8f8f8;padding:32px 40px;border-top:1px solid #e8e8e8;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="text-align:center;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
                                               font-size:12px;line-height:1.6;color:#888888;">

                                        @if(!empty($author))
                                        <p style="margin:0 0 8px;">Written by <strong>{{ $author }}</strong></p>
                                        @endif

                                        <p style="margin:0 0 8px;">
                                            You're receiving this because you subscribed to {{ $fromName ?? config('app.name') }}.
                                        </p>

                                        <p style="margin:0 0 8px;">
                                            <a href="{{ $preferencesUrl }}"
                                               style="color:#555555;text-decoration:underline;">Manage preferences</a>
                                            &nbsp;&middot;&nbsp;
                                            <a href="{{ $unsubscribeUrl }}"
                                               style="color:#555555;text-decoration:underline;">Unsubscribe</a>
                                        </p>

                                        {{-- Physical address — required by CAN-SPAM / CASL --}}
                                        @php $address = config('newsletter.physical_address'); @endphp
                                        @if($address)
                                        <p style="margin:0 0 8px;color:#aaaaaa;">{{ $address }}</p>
                                        @endif

                                        <p style="margin:0;color:#aaaaaa;">
                                            &copy; {{ date('Y') }} {{ $fromName ?? config('app.name') }}. All rights reserved.
                                        </p>

                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>
                {{-- /Email container --}}

            </td>
        </tr>
    </table>
    {{-- /Email wrapper --}}

</body>
</html>
