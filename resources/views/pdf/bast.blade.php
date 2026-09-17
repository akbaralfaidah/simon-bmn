<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $bast->bast_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; line-height: 1.6; }
        @page { margin: 28mm 18mm 22mm; }
        h1 { font-size: 16px; text-align: center; } .center { text-align: center; }
        thead { display: table-header-group; } tr { page-break-inside: avoid; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; } td, th { border: 1px solid #555; padding: 8px; }
        .notice { border: 1px solid #555; padding: 10px; } .sign td { border: none; width: 50%; text-align: center; }
    </style>
</head>
<body>
<p class="center">{{ data_get($bast->snapshot, 'header', 'SIMON — Sistem Informasi Barang Milik Negara') }}</p>
<h1>{{ data_get($bast->snapshot, 'title', \App\Services\DocumentService::TEMPLATES[$bast->bast_type] ?? 'BERITA ACARA SERAH TERIMA BARANG MILIK NEGARA') }}</h1>
<p class="center">Nomor: {{ $bast->bast_number }}<br>Dibuat: {{ $bast->created_at->format('d-m-Y') }}</p>
<p class="notice">DRAF/CETAK SISTEM — bukan bukti tanda tangan. Dokumen sah diperiksa melalui berkas bertanda tangan yang diunggah dan diverifikasi. Status berkas: {{ $bast->status }}.</p>
<p>Yang menyerahkan: {{ data_get($bast->snapshot, 'issuer', $bast->issuer?->name ?? '-') }}<br>
Yang menerima: {{ data_get($bast->snapshot, 'receiver', $bast->receiver?->name ?? '-') }}</p>
<p>Keperluan: {{ data_get($bast->snapshot, 'purpose', '-') }}<br>
Periode: {{ data_get($bast->snapshot, 'start_date', '-') }} s.d. {{ data_get($bast->snapshot, 'end_date', '-') }}</p>
<table>
    <thead><tr><th>No.</th><th>Nama barang</th><th>Kode barang</th><th>NUP</th><th>Kondisi tercatat</th></tr></thead>
    <tbody>
    @forelse(data_get($bast->snapshot, 'items', []) as $item)
        <tr><td>{{ $loop->iteration }}</td><td>{{ $item['name'] }}</td><td>{{ $item['item_code'] ?? '-' }}</td><td>{{ $item['nup'] ?? '-' }}</td><td>{{ $item['condition'] ?? '-' }}</td></tr>
    @empty
        <tr><td colspan="5">Dokumen lama belum memiliki snapshot. Periksa dokumen sumber sebelum penggunaan.</td></tr>
    @endforelse
    </tbody>
</table>
<p>Data di atas merupakan rekaman pada saat dokumen dibuat. Verifikasi identitas, kelengkapan dan kondisi fisik sebelum penandatanganan.</p>
<table class="sign"><tr><td>Yang menyerahkan<br><br><br><br>(__________________)</td><td>Yang menerima<br><br><br><br>(__________________)</td></tr></table>
</body>
</html>
