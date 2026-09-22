@php
    $logoKlh = isset($message) ? $message->embed(public_path('images/logo-klh.png')) : asset('images/logo-klh.png');
    $logoGakkum = isset($message) ? $message->embed(public_path('images/logo-gakkum.png')) : asset('images/logo-gakkum.png');
    $unit = $unitName ?? ($user->profile?->unit?->name ?? 'Balai Gakkum LH Wilayah Sumatera');
    
    $statusConfig = match($status) {
        'active' => [
            'badge_bg' => '#ecfdf5',
            'badge_text' => '#15803d',
            'badge_border' => '#a7f3d0',
            'label' => 'Akun Aktif',
            'title' => 'Akun Kedinasan Anda Telah Diaktifkan',
            'subtitle' => 'Pemberitahuan Status Akun Pengguna',
            'accent' => '#15803d',
            'accent_gradient' => 'linear-gradient(90deg, #15803d 0%, #16a34a 50%, #22c55e 100%)',
            'show_cta' => true,
        ],
        'suspended' => [
            'badge_bg' => '#fef2f2',
            'badge_text' => '#b91c1c',
            'badge_border' => '#fecaca',
            'label' => 'Akun Ditangguhkan',
            'title' => 'Pemberitahuan Penangguhan Akses Akun',
            'subtitle' => 'Pemberitahuan Status Akun Pengguna',
            'accent' => '#b91c1c',
            'accent_gradient' => 'linear-gradient(90deg, #991b1b 0%, #dc2626 50%, #f87171 100%)',
            'show_cta' => false,
        ],
        default => [
            'badge_bg' => '#fffbeb',
            'badge_text' => '#b45309',
            'badge_border' => '#fde68a',
            'label' => 'Menunggu Peninjauan',
            'title' => 'Pembaruan Status Akun Pengguna',
            'subtitle' => 'Pemberitahuan Status Akun Pengguna',
            'accent' => '#d97706',
            'accent_gradient' => 'linear-gradient(90deg, #b45309 0%, #d97706 50%, #f59e0b 100%)',
            'show_cta' => false,
        ],
    };
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $statusConfig['title'] }} - SIMON BMN Gakkum</title>
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
                    
                    <!-- Decorative Top Accent Bar -->
                    <tr>
                        <td style="height: 6px; background: {{ $statusConfig['accent_gradient'] }};"></td>
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
                                <div style="display: inline-block; font-size: 11px; font-weight: 700; color: {{ $statusConfig['accent'] }}; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 6px;">
                                    {{ $statusConfig['subtitle'] }}
                                </div>
                                <h1 style="margin: 0; font-size: 22px; font-weight: 800; color: #0f172a; line-height: 1.3; letter-spacing: -0.4px;">
                                    {{ $statusConfig['title'] }}
                                </h1>
                            </div>

                            <!-- Greeting -->
                            <p style="margin: 0 0 16px 0; font-size: 14px; line-height: 1.6; color: #334155;">
                                Yth. Bapak/Ibu <strong>{{ $user->name }}</strong>,
                            </p>

                            @if($status === 'active')
                                <p style="margin: 0 0 24px 0; font-size: 14px; line-height: 1.6; color: #475569;">
                                    Kabar baik! Akun Anda pada aplikasi <strong>SIMON (Sistem Informasi Manajemen BMN)</strong> telah diverifikasi dan <strong>diaktifkan</strong> oleh koordinator administrasi. Anda sekarang dapat mengakses sistem, melihat inventaris, dan melakukan pengajuan peminjaman atau pengelolaan BMN.
                                </p>
                            @elseif($status === 'suspended')
                                <p style="margin: 0 0 24px 0; font-size: 14px; line-height: 1.6; color: #475569;">
                                    Kami informasikan bahwa akses akun Anda pada aplikasi <strong>SIMON BMN</strong> saat ini telah <strong>ditangguhkan</strong> oleh koordinator administrasi. Sesi login aktif telah diakhiri untuk keamanan sistem.
                                </p>
                            @else
                                <p style="margin: 0 0 24px 0; font-size: 14px; line-height: 1.6; color: #475569;">
                                    Kami informasikan bahwa telah dilakukan penyesuaian pada status akun Anda pada aplikasi <strong>SIMON BMN</strong> oleh koordinator administrasi.
                                </p>
                            @endif

                            <!-- Data Summary Box -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 24px; overflow: hidden;">
                                <tr>
                                    <td style="padding: 14px 20px; border-bottom: 1px solid #edf2f7; background-color: #f1f5f9;">
                                        <span style="font-size: 12px; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.5px;">
                                            Rincian Pembaruan Akun
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="font-size: 13px; color: #334155;">
                                            <tr>
                                                <td width="38%" style="padding: 5px 0; color: #64748b;">Nama Pegawai</td>
                                                <td width="62%" style="padding: 5px 0; font-weight: 600; color: #0f172a;">{{ $user->name }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 5px 0; color: #64748b;">Email Kedinasan</td>
                                                <td style="padding: 5px 0; font-weight: 600; color: #0f172a;">{{ $user->email }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 5px 0; color: #64748b;">Status Baru</td>
                                                <td style="padding: 5px 0;">
                                                    <span style="display: inline-block; font-size: 11px; font-weight: 700; color: {{ $statusConfig['badge_text'] }}; background-color: {{ $statusConfig['badge_bg'] }}; border: 1px solid {{ $statusConfig['badge_border'] }}; padding: 3px 10px; border-radius: 6px;">
                                                        {{ $statusConfig['label'] }}
                                                    </span>
                                                </td>
                                            </tr>
                                            @if(!empty($role))
                                            <tr>
                                                <td style="padding: 5px 0; color: #64748b;">Mandat / Peran</td>
                                                <td style="padding: 5px 0; font-weight: 600; color: #0f172a;">{{ $role }}</td>
                                            </tr>
                                            @endif
                                            <tr>
                                                <td style="padding: 5px 0; color: #64748b;">Unit Kerja</td>
                                                <td style="padding: 5px 0; font-weight: 600; color: #0f172a;">{{ $unit }}</td>
                                            </tr>
                                            @if(!empty($reason))
                                            <tr>
                                                <td style="padding: 5px 0; color: #64748b; vertical-align: top;">Catatan Koordinator</td>
                                                <td style="padding: 5px 0; color: #334155; line-height: 1.4; font-style: italic;">"{{ $reason }}"</td>
                                            </tr>
                                            @endif
                                            @if(!empty($updatedBy))
                                            <tr>
                                                <td style="padding: 5px 0; color: #64748b;">Ditinjau Oleh</td>
                                                <td style="padding: 5px 0; color: #64748b;">{{ $updatedBy }}</td>
                                            </tr>
                                            @endif
                                            <tr>
                                                <td style="padding: 5px 0; color: #64748b;">Waktu Pembaruan</td>
                                                <td style="padding: 5px 0; color: #64748b;">{{ now()->translatedFormat('d F Y, H:i') }} WIB</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            @if($statusConfig['show_cta'])
                            <!-- Primary CTA Button -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 28px;">
                                <tr>
                                    <td align="center">
                                        <table border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td align="center" style="border-radius: 10px; background-color: #15803d; box-shadow: 0 4px 12px rgba(21, 128, 61, 0.3);">
                                                    <a href="{{ $loginUrl ?? url('/login') }}" target="_blank" class="btn-action" style="display: inline-block; padding: 14px 36px; font-size: 14px; font-weight: 700; color: #ffffff; text-decoration: none; border-radius: 10px; letter-spacing: 0.2px; text-align: center;">
                                                        Masuk ke Sistem SIMON &rarr;
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            @endif

                            <!-- Help/Information Note -->
                            <div style="background-color: #f8fafc; border-left: 4px solid {{ $statusConfig['accent'] }}; border-radius: 0 8px 8px 0; padding: 14px 16px; margin-bottom: 24px;">
                                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                    <tr>
                                        <td style="font-size: 12px; line-height: 1.5; color: #475569;">
                                            @if($status === 'active')
                                                <strong>Petunjuk:</strong> Gunakan alamat email dan kata sandi yang Anda daftarkan sebelumnya untuk masuk ke sistem. Pastikan untuk selalu menjaga kerahasiaan kredensial akun kedinasan Anda.
                                            @elseif($status === 'suspended')
                                                <strong>Informasi:</strong> Apabila Anda merasa penangguhan ini adalah kekeliruan atau membutuhkan pemulihan hak akses operasional, silakan hubungi koordinator BMN atau Sub Bagian Tata Usaha.
                                            @else
                                                <strong>Informasi:</strong> Status akun Anda sedang dalam proses peninjauan berkala oleh administrator.
                                            @endif
                                        </td>
                                    </tr>
                                </table>
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
