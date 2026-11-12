<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan SI-GORAY</title>
    <style>
        @page { margin: 22px 25px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #222; }
        h1 { font-size: 17px; margin: 0 0 6px; }
        .info { margin-bottom: 12px; color: #555; font-size: 9px; }
        table { border-collapse: collapse; width: 100%; table-layout: fixed; }
        th, td { border: 1px solid #aaa; padding: 5px; overflow-wrap: anywhere; word-wrap: break-word; }
        th { background: #eceff3; font-size: 8px; text-align: left; }
        td { font-size: 8px; }
        tr { page-break-inside: avoid; }
        .footer { margin-top: 10px; font-size: 8px; color: #666; }
    </style>
</head>
<body>
    <h1>Laporan Operasional SI-GORAY</h1>
    <div class="info">
        Periode: {{ $tanggalMulai }} s.d. {{ $tanggalAkhir }} |
        Jenis: {{ ucfirst($jenis) }} |
        Jumlah data: {{ $rows->count() }}
    </div>
    <table>
        <thead>
            <tr>
                @foreach ($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="footer">Dibuat: {{ $generatedAt->format('d/m/Y H:i') }}. Laporan menggunakan data sistem saat ekspor.</div>
</body>
</html>
