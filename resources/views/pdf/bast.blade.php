<!DOCTYPE html>
<html>
<head>
    <title>BAST {{ $bast->bast_number }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .title { font-weight: bold; font-size: 14px; text-decoration: underline; text-align: center; }
        .number { text-align: center; margin-bottom: 20px; }
        .content { margin-top: 20px; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table, th, td { border: 1px solid black; }
        th, td { padding: 8px; text-align: left; }
        .signatures { margin-top: 50px; width: 100%; }
        .signatures td { border: none; text-align: center; width: 50%; }
    </style>
</head>
<body>
    <div class="header">
        <strong>KEMENTERIAN LINGKUNGAN HIDUP DAN KEHUTANAN</strong><br>
        DIREKTORAT JENDERAL PENEGAKAN HUKUM LINGKUNGAN HIDUP DAN KEHUTANAN<br>
        BALAI PENGAMANAN DAN PENEGAKAN HUKUM LINGKUNGAN HIDUP DAN KEHUTANAN WILAYAH SUMATERA
    </div>
    
    <div class="title">BERITA ACARA SERAH TERIMA BARANG MILIK NEGARA</div>
    <div class="number">Nomor: {{ $bast->bast_number }}</div>
    
    <div class="content">
        Pada hari ini, tanggal {{ now()->translatedFormat('d F Y') }}, kami yang bertanda tangan di bawah ini:
        
        <br><br>
        <strong>Pihak Pertama (Yang Menyerahkan):</strong><br>
        Nama: {{ $bast->issuer->name ?? '..................' }}<br>
        NIP: {{ $bast->issuer->employeeProfile->nip ?? '..................' }}<br>
        
        <br>
        <strong>Pihak Kedua (Yang Menerima):</strong><br>
        Nama: {{ $bast->receiver->name ?? '..................' }}<br>
        NIP: {{ $bast->receiver->employeeProfile->nip ?? '..................' }}<br>
        
        <br>
        Telah melakukan serah terima Barang Milik Negara dengan rincian sebagai berikut:
        
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Barang</th>
                    <th>NUP</th>
                    <th>Kondisi</th>
                </tr>
            </thead>
            <tbody>
                @if($bast->reference && $bast->reference->items)
                    @foreach($bast->reference->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->asset->name ?? '-' }}</td>
                        <td>{{ $item->asset->nup ?? '-' }}</td>
                        <td>{{ $item->asset->condition ?? '-' }}</td>
                    </tr>
                    @endforeach
                @else
                    <tr><td colspan="4">Data barang tidak ditemukan.</td></tr>
                @endif
            </tbody>
        </table>
        
        <br>
        Demikian Berita Acara Serah Terima ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.
    </div>
    
    <table class="signatures">
        <tr>
            <td>
                Yang Menerima,<br>Pihak Kedua
                <br><br><br><br><br>
                <strong>{{ $bast->receiver->name ?? '..................' }}</strong>
            </td>
            <td>
                Yang Menyerahkan,<br>Pihak Pertama
                <br><br><br><br><br>
                <strong>{{ $bast->issuer->name ?? '..................' }}</strong>
            </td>
        </tr>
    </table>
</body>
</html>
