@php
    $logoKlh = isset($message) ? $message->embed(public_path('images/logo-klh.png')) : asset('images/logo-klh.png');
    $logoGakkum = isset($message) ? $message->embed(public_path('images/logo-gakkum.png')) : asset('images/logo-gakkum.png');
    $unitName = $user->profile?->unit?->name ?? 'Balai Gakkum LH Wilayah Sumatera';
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Verifikasi Alamat Email - SIMON BMN Gakkum</title>
    <style type="text/css">
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        body { margin: 0; padding: 0; width: 100% !important; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        @media screen and (max-width: 600px) {
            .email-container { width: 100% !important; margin: auto !important; }
            .content-padding { padding: 24px 20px !important; }
            .header-padding { padding: 20px 16px !important; }
            .btn-action { display: block !important; width: 100% !important; box-sizing: border-box !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; color: #1e293b;">

    <!-- Wrapper Table -->
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f1f5f9; padding: 30px 10px;">
        <tr>
            <td align="center">
                <!-- Container Card (Max 600px) -->
                <table border="0" cellpadding="0" cellspacing="0" width="100%" class="email-container" style="max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06); border: 1px solid #e2e8f0;">
                    
                    <!-- Top Green Decorative Accent Bar -->
                    <tr>
                        <td style="height: 6px; background: linear-gradient(90deg, #15803d 0%, #16a34a 50%, #4ade80 100%);"></td>
                    </tr>

                    <!-- Official Header -->
                    <tr>
                        <td class="header-padding" style="padding: 28px 36px 20px 36px; border-bottom: 1px solid #f1f5f9; background-color: #ffffff;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <!-- Logos -->
                                    <td align="left" valign="middle" style="padding-right: 14px;">
                                        <table border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding-right: 12px;">
                                                    <img src="{{ $logoKlh }}" alt="Logo Kementerian LH" width="46" style="display: block; width: 46px; height: auto; max-height: 52px;" />
                                                </td>
                                                <td style="padding-right: 14px; border-right: 1px solid #e2e8f0;">
                                                    <img src="{{ $logoGakkum }}" alt="Logo Ditjen Gakkum LH" width="46" style="display: block; width: 46px; height: auto; max-height: 52px;" />
                                                </td>
                                                <td style="padding-left: 14px;">
                                                    <div style="font-size: 15px; font-weight: 800; color: #0f172a; letter-spacing: -0.3px; line-height: 1.2;">
                                                        SIMON <span style="display: inline-block; font-size: 10px; font-weight: 700; background-color: #ecfdf5; color: #15803d; padding: 2px 8px; border-radius: 4px; border: 1px solid #a7f3d0; margin-left: 4px; text-transform: uppercase;">Sumatera</span>
                                                    </div>
                                                    <div style="font-size: 11px; color: #64748b; font-weight: 500; margin-top: 2px; line-height: 1.3;">
                                                        Balai Pengamanan & Penegakan Hukum LH Wilayah Sumatera
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Email Body -->
                    <tr>
                        <td class="content-padding" style="padding: 36px 36px 28px 36px;">
                            
                            <!-- Hero Title -->
                            <div style="text-align: left; margin-bottom: 24px;">
                                <div style="display: inline-block; font-size: 11px; font-weight: 700; color: #15803d; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 6px;">
                                    Konfirmasi Pendaftaran Pegawai
                                </div>
                                <h1 style="margin: 0; font-size: 22px; font-weight: 800; color: #0f172a; line-height: 1.3; letter-spacing: -0.4px;">
                                    Verifikasi Alamat Email Akun Anda
                                </h1>
                            </div>

                            <!-- Greeting -->
                            <p style="margin: 0 0 16px 0; font-size: 14px; line-height: 1.6; color: #334155;">
                                Yth. Bapak/Ibu <strong>{{ $user->name }}</strong>,
                            </p>

                            <p style="margin: 0 0 24px 0; font-size: 14px; line-height: 1.6; color: #475569;">
                                Terima kasih telah mendaftarkan akun pada <strong>SIMON (Sistem Informasi Manajemen BMN)</strong> Balai Penegakan Hukum LH Wilayah Sumatera. Untuk memastikan keamanan akses sistem dan keabsahan akun kedinasan Anda, silakan lakukan verifikasi kepemilikan alamat email ini.
                            </p>

                            <!-- Data Summary Box -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 28px; overflow: hidden;">
                                <tr>
                                    <td style="padding: 16px 20px; border-bottom: 1px solid #edf2f7; background-color: #f1f5f9;">
                                        <span style="font-size: 12px; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.5px;">
                                            Ringkasan Data Pendaftaran
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="font-size: 13px; color: #334155;">
                                            <tr>
                                                <td width="38%" style="padding: 4px 0; color: #64748b;">Nama Pegawai</td>
                                                <td width="62%" style="padding: 4px 0; font-weight: 600; color: #0f172a;">{{ $user->name }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 4px 0; color: #64748b;">Email Kedinasan</td>
                                                <td style="padding: 4px 0; font-weight: 600; color: #0f172a;">{{ $user->email }}</td>
                                            </tr>
                                            @if($user->profile?->nip)
                                            <tr>
                                                <td style="padding: 4px 0; color: #64748b;">NIP</td>
                                                <td style="padding: 4px 0; font-weight: 600; color: #0f172a;">{{ $user->profile->nip }}</td>
                                            </tr>
                                            @endif
                                            <tr>
                                                <td style="padding: 4px 0; color: #64748b;">Unit Kerja</td>
                                                <td style="padding: 4px 0; font-weight: 600; color: #15803d;">{{ $unitName }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 4px 0; color: #64748b;">Status Awal</td>
                                                <td style="padding: 4px 0;">
                                                    <span style="display: inline-block; font-size: 11px; font-weight: 600; color: #b45309; background-color: #fef3c7; padding: 2px 8px; border-radius: 4px;">
                                                        Menunggu Verifikasi & Aktivasi
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Primary CTA Button -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 28px;">
                                <tr>
                                    <td align="center">
                                        <table border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td align="center" style="border-radius: 10px; background-color: #15803d; box-shadow: 0 4px 12px rgba(21, 128, 61, 0.3);">
                                                    <a href="{{ $url }}" target="_blank" class="btn-action" style="display: inline-block; padding: 14px 34px; font-size: 14px; font-weight: 700; color: #ffffff; text-decoration: none; border-radius: 10px; letter-spacing: 0.2px; text-align: center;">
                                                        Verifikasi Akun Anda Sekarang &rarr;
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Next Steps Info Card -->
                            <div style="background-color: #ecfdf5; border-left: 4px solid #15803d; border-radius: 0 8px 8px 0; padding: 14px 16px; margin-bottom: 24px;">
                                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                    <tr>
                                        <td style="font-size: 12px; line-height: 1.5; color: #065f46;">
                                            <strong>Langkah Setelah Verifikasi:</strong> Setelah alamat email terverifikasi, petugas Tata Usaha akan mengaktifkan akun Anda agar dapat langsung mengajukan formulir peminjaman dan pengelolaan BMN.
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <p style="margin: 0 0 10px 0; font-size: 12px; line-height: 1.5; color: #94a3b8;">
                                * Tautan verifikasi di atas berlaku selama <strong>60 menit</strong>. Jika Anda tidak merasa melakukan pendaftaran akun SIMON ini, silakan abaikan email ini.
                            </p>

                            <!-- Direct Link Fallback -->
                            <div style="border-top: 1px solid #f1f5f9; padding-top: 18px; margin-top: 20px;">
                                <p style="margin: 0 0 6px 0; font-size: 11px; color: #64748b; line-height: 1.4;">
                                    Jika tombol di atas mengalami kendala saat diklik, salin dan tempelkan tautan berikut ke bilah alamat peramban (browser) Anda:
                                </p>
                                <p style="margin: 0; font-size: 11px; word-break: break-all; color: #15803d; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; background-color: #f8fafc; padding: 8px 10px; border-radius: 6px; border: 1px solid #e2e8f0;">
                                    <a href="{{ $url }}" style="color: #15803d; text-decoration: underline;">{{ $url }}</a>
                                </p>
                            </div>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 24px 36px 30px 36px; background-color: #f8fafc; border-top: 1px solid #e2e8f0; text-align: center;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td style="font-size: 12px; color: #475569; line-height: 1.5; padding-bottom: 12px;">
                                        <strong>Sub Bagian Tata Usaha</strong><br />
                                        Balai Pengamanan & Penegakan Hukum Lingkungan Hidup Wilayah Sumatera<br />
                                        <span style="color: #64748b; font-size: 11px;">Jl. Arif Rahman Hakim No. 10, Telanaipura, Kota Jambi, Jambi</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="border-top: 1px solid #e2e8f0; padding-top: 14px; font-size: 11px; color: #94a3b8; line-height: 1.4;">
                                        Email ini dibuat dan dikirim secara otomatis oleh Sistem Informasi SIMON BMN Gakkum.<br />
                                        Mohon tidak membalas email ini secara langsung (no-reply).<br />
                                        &copy; {{ date('Y') }} Balai Gakkum LH Wilayah Sumatera. Seluruh hak cipta dilindungi.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
