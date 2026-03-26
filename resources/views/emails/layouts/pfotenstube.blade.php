@php
    $home = rtrim((string) config('services.pfotenstube.homepage_url'), '/');
    /** Raster dog mascot extracted from Homepage public/img/logo.svg (same asset as navbar). */
    $dogPath = public_path('img/pfotenstube-dog.png');
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title', 'Pfotenstube')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
</head>
<body style="margin:0;padding:0;background-color:#F8F8F8;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
    <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">
        @yield('preheader')
        &nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;
    </div>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#F8F8F8;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:560px;background-color:#FEF5E8;border-radius:8px;overflow:hidden;border:1px solid #e8dfd0;box-shadow:0 2px 8px rgba(109,56,26,0.08);">
                    {{-- Header --}}
                    <tr>
                        <td align="center" style="background-color:#2d1f1c;padding:20px 24px;border-bottom:3px solid #CDA275;">
                            <a href="{{ $home !== '' ? $home : 'https://pfotenstube.at' }}" target="_blank" rel="noopener noreferrer" style="text-decoration:none;color:#ffffff;">
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:0 auto;">
                                    <tr>
                                        <td valign="middle" style="padding-right:14px;">
                                            {{-- Same font family as Homepage layout (Poppins); vector wordmark in logo.svg is separate from the dog raster --}}
                                            <span style="font-family:'Poppins','Segoe UI',Roboto,Arial,sans-serif;font-weight:700;font-size:21px;color:#ffffff;letter-spacing:0.02em;line-height:1.2;">
                                                Pfotenstube.at
                                            </span>
                                        </td>
                                        @if(file_exists($dogPath))
                                            <td valign="middle" style="line-height:0;">
                                                <img src="{{ $message->embed($dogPath) }}" alt="" width="40" height="40" style="display:block;width:40px;height:40px;border:0;outline:none;vertical-align:middle;">
                                            </td>
                                        @endif
                                    </tr>
                                </table>
                            </a>
                        </td>
                    </tr>
                    {{-- Accent strip --}}
                    <tr>
                        <td style="height:4px;background-color:#CDA275;line-height:4px;font-size:0;">&nbsp;</td>
                    </tr>
                    {{-- Body --}}
                    <tr>
                        <td style="padding:32px 28px 36px;font-family:'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;font-size:16px;line-height:1.55;color:#6D381A;">
                            @hasSection('badge')
                                <div style="margin-bottom:20px;">
                                    @yield('badge')
                                </div>
                            @endif
                            <h1 style="margin:0 0 16px;font-family:Georgia,'Times New Roman',serif;font-size:22px;font-weight:600;color:#6D381A;line-height:1.3;">
                                @yield('heading')
                            </h1>
                            <div style="color:#6D381A;">
                                @yield('content')
                            </div>
                            @hasSection('cta')
                                <div style="margin-top:28px;text-align:center;">
                                    @yield('cta')
                                </div>
                            @endif
                        </td>
                    </tr>
                    {{-- Footer --}}
                    <tr>
                        <td style="padding:20px 28px 28px;background-color:#fdfbf7;border-top:1px solid #e8dfd0;">
                            <p style="margin:0 0 8px;font-family:'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;font-size:13px;line-height:1.5;color:#6D381A;opacity:0.9;">
                                Mit freundlichen Grüßen<br>
                                <strong style="color:#6D381A;">Ihr Team der Pfotenstube</strong>
                            </p>
                            @if($home !== '')
                                <p style="margin:16px 0 0;font-family:'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;font-size:12px;line-height:1.5;">
                                    <a href="{{ $home }}" style="color:#CDA275;text-decoration:none;font-weight:600;border-bottom:1px solid #CDA275;">pfotenstube.at</a>
                                </p>
                            @endif
                        </td>
                    </tr>
                </table>
                <p style="margin:20px 0 0;font-family:'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;font-size:11px;line-height:1.4;color:#8a7568;max-width:560px;text-align:center;">
                    Diese E-Mail wurde automatisch versendet. Bitte antworten Sie nicht direkt auf diese Nachricht, falls Sie Fragen haben, kontaktieren Sie uns über unsere Website.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
