@php
    $logoKlh = isset($message) ? $message->embed(public_path('images/logo-klh.png')) : asset('images/logo-klh.png');
    $logoGakkum = isset($message) ? $message->embed(public_path('images/logo-gakkum.png')) : asset('images/logo-gakkum.png');
    $isRevoked = $action === 'revoked';
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $isRevoked ? 'Pencabutan Mandat Penugasan' : 'Pembaruan Mandat Penugasan' }} - SIMON BMN Gakkum</title>
    <style type="text/css">
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        body { margin: 0; padding: 0; width: 100% !important; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        @media screen and (max-width: 600px) {
            .email-container { width: 100% !important; margin: auto !important; }
            .content-padding { padding: 24px 20px !important; }
            .header-padding { padding: 20px 16px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; color: #1e293b;">

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f1f5f9; padding: 30px 10px;">
        <tr>
            <td align="center">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" class="email-container" style="max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06); border: 1px solid #e2e8f0;">
                    
                    <!-- Decorative Top Bar -->
                    <tr>
                        <td style="height: 6px; background: {{ $isRevoked ? 'linear-gradient(90deg, #991b1b 0%, #dc2626 50%, #f87171 100%)' : 'linear-gradient(90deg, #0284c7 0%, #0ea5e9 50%, #38bdf8 100%)' }};"></td>
                    </tr>

                    <!-- Header -->
                    <tr>
                        <td class="header-padding" style="padding: 28px 36px 20px 36px; border-bottom: 1px solid #f1f5f9; background-color: #ffffff;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
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

                    <!-- Body -->
                    <tr>
                        <td class="content-padding" style="padding: 36px 36px 28px 36px;">
                            
                            <div style="text-align: left; margin-bottom: 24px;">
                                <div style="display: inline-block; font-size: 11px; font-weight: 700; color: {{ $isRevoked ? '#dc2626' : '#0284c7' }}; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 6px;">
                                    Pemberitahuan Mandat Operasional
                                </div>
                                <h1 style="margin: 0; font-size: 22px; font-weight: 800; color: #0f172a; line-height: 1.3; letter-spacing: -0.4px;">
                                    {{ $isRevoked ? 'Pencabutan Mandat Penugasan' : 'Pembaruan Mandat Penugasan' }}
                                </h1>
                            </div>

                            <p style="margin: 0 0 16px 0; font-size: 14px; line-height: 1.6; color: #334155;">
                                Yth. Bapak/Ibu <strong>{{ $user->name }}</strong>,
                            </p>

                            @if($isRevoked)
                                <p style="margin: 0 0 24px 0; font-size: 14px; line-height: 1.6; color: #475569;">
                                    Kami informasikan bahwa mandat penugasan Anda sebagai <strong>{{ $roleName }}</strong> pada sistem <strong>SIMON BMN</strong> telah <strong>dicabut</strong> oleh pengelola administrasi. Sesi aktif telah diperbarui sesuai pencabutan ini.
                                </p>
                            @else
                                <p style="margin: 0 0 24px 0; font-size: 14px; line-height: 1.6; color: #475569;">
                                    Kami informasikan bahwa rincian mandat penugasan Anda sebagai <strong>{{ $roleName }}</strong> pada sistem <strong>SIMON BMN</strong> telah <strong>diperbarui</strong> oleh pengelola administrasi.
                                </p>
                            @endif

                            <!-- Details Table -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 24px; overflow: hidden;">
                                <tr>
                                    <td style="padding: 14px 20px; border-bottom: 1px solid #edf2f7; background-color: #f1f5f9;">
                                        <span style="font-size: 12px; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.5px;">
                                            Rincian Penugasan
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="font-size: 13px; color: #334155;">
                                            <tr>
                                                <td width="38%" style="padding: 5px 0; color: #64748b;">Peran / Mandat</td>
                                                <td width="62%" style="padding: 5px 0; font-weight: 700; color: #0f172a;">{{ $roleName }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 5px 0; color: #64748b;">Tindakan</td>
                                                <td style="padding: 5px 0;">
                                                    <span style="display: inline-block; font-size: 11px; font-weight: 700; color: {{ $isRevoked ? '#b91c1c' : '#0369a1' }}; background-color: {{ $isRevoked ? '#fef2f2' : '#f0f9ff' }}; border: 1px solid {{ $isRevoked ? '#fecaca' : '#bae6fd' }}; padding: 3px 10px; border-radius: 6px;">
                                                        {{ $isRevoked ? 'Mandat Dicabut' : 'Mandat Diperbarui' }}
                                                    </span>
                                                </td>
                                            </tr>
                                            @if(!empty($unitName))
                                            <tr>
                                                <td style="padding: 5px 0; color: #64748b;">Unit Penugasan</td>
                                                <td style="padding: 5px 0; font-weight: 600; color: #0f172a;">{{ $unitName }}</td>
                                            </tr>
                                            @endif
                                            @if(!empty($roomName))
                                            <tr>
                                                <td style="padding: 5px 0; color: #64748b;">Cakupan Ruangan</td>
                                                <td style="padding: 5px 0; color: #0f172a;">{{ $roomName }}</td>
                                            </tr>
                                            @endif
                                            @if(!empty($endsAt))
                                            <tr>
                                                <td style="padding: 5px 0; color: #64748b;">Batas Penugasan</td>
                                                <td style="padding: 5px 0; color: #0f172a;">{{ \Carbon\Carbon::parse($endsAt)->translatedFormat('d F Y') }}</td>
                                            </tr>
                                            @endif
                                            @if(!empty($reason))
                                            <tr>
                                                <td style="padding: 5px 0; color: #64748b; vertical-align: top;">Alasan / Catatan</td>
                                                <td style="padding: 5px 0; color: #334155; line-height: 1.4; font-style: italic;">"{{ $reason }}"</td>
                                            </tr>
                                            @endif
                                            @if(!empty($updatedBy))
                                            <tr>
                                                <td style="padding: 5px 0; color: #64748b;">Diproses Oleh</td>
                                                <td style="padding: 5px 0; color: #64748b;">{{ $updatedBy }}</td>
                                            </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <div style="background-color: #f8fafc; border-left: 4px solid {{ $isRevoked ? '#dc2626' : '#0284c7' }}; border-radius: 0 8px 8px 0; padding: 14px 16px; margin-bottom: 24px;">
                                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                    <tr>
                                        <td style="font-size: 12px; line-height: 1.5; color: #475569;">
                                            @if($isRevoked)
                                                <strong>Catatan:</strong> Pencabutan mandat ini mencatat riwayat penugasan secara resmi. Apabila terdapat tugas operasional atau BMN yang sedang berlangsung, pastikan telah dikoordinasikan dengan petugas pengganti.
                                            @else
                                                <strong>Catatan:</strong> Hak akses operasional Anda dalam sistem telah disesuaikan dengan mandat terbaru. Silakan masuk kembali jika diperlukan.
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
