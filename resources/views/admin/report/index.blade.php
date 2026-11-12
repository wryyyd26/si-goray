@extends('layouts.admin')

@section('title', 'Laporan')
@section('page', 'Laporan')

@section('content')
    <div class="report-page">

        <div class="report-page-header">
            <div>
                <h1>Laporan</h1>
                <p>
                    Pantau dan rekap data operasional SI-GORAY
                    berdasarkan periode yang dipilih.
                </p>
            </div>

            <div class="report-export-actions">
                <button type="button" class="report-export-btn excel" data-report-export="excel" data-export-url="{{ route('admin.report.export', ['format' => 'excel']) }}">
                    <span class="material-symbols-rounded">table_view</span>
                    Ekspor Excel
                </button>

                <button type="button" class="report-export-btn pdf" data-report-export="pdf" data-export-url="{{ route('admin.report.export', ['format' => 'pdf']) }}">
                    <span class="material-symbols-rounded">picture_as_pdf</span>
                    Ekspor PDF
                </button>
            </div>
        </div>

        <div class="report-filter-card">
            <div class="report-filter-heading">
                <div class="report-filter-icon">
                    <span class="material-symbols-rounded">filter_alt</span>
                </div>
                <div>
                    <h3>Filter Laporan</h3>
                    <p>Tentukan periode dan jenis data yang ingin ditampilkan.</p>
                </div>
            </div>

            @if ($errors->any())
                <div class="report-filter-errors" role="alert" style="color:#b91c1c;margin:12px 0;">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('admin.report.index') }}" method="GET" class="report-filter-form">

                <div class="report-field">
                    <label for="tanggal_mulai">Tanggal Mulai</label>
                    <input type="date" id="tanggal_mulai" name="tanggal_mulai" value="{{ old('tanggal_mulai', $tanggalMulai) }}"
                        required>
                </div>

                <div class="report-field">
                    <label for="tanggal_akhir">Tanggal Akhir</label>
                    <input type="date" id="tanggal_akhir" name="tanggal_akhir" value="{{ old('tanggal_akhir', $tanggalAkhir) }}"
                        required>
                </div>

                <div class="report-field">
                    <label for="jenis">Jenis Laporan</label>
                    <select id="jenis" name="jenis">
                        <option value="semua" {{ $jenis === 'semua' ? 'selected' : '' }}>
                            Semua Laporan
                        </option>
                        <option value="registrasi" {{ $jenis === 'registrasi' ? 'selected' : '' }}>
                            Registrasi
                        </option>
                        <option value="tiket" {{ $jenis === 'tiket' ? 'selected' : '' }}>
                            Penggunaan Tiket
                        </option>
                        <option value="pembayaran" {{ $jenis === 'pembayaran' ? 'selected' : '' }}>
                            Pembayaran
                        </option>
                    </select>
                </div>

                <div class="report-filter-buttons">
                    <button type="submit" class="report-apply-btn">
                        <span class="material-symbols-rounded">search</span>
                        Terapkan
                    </button>

                    <a href="{{ route('admin.report.index') }}" class="report-reset-btn">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="report-summary-grid">
            <div class="report-summary-card">
                <div class="report-summary-top">
                    <span class="report-summary-icon red">
                        <span class="material-symbols-rounded">app_registration</span>
                    </span>
                    <span class="report-summary-caption">Registrasi berhasil</span>
                </div>
                <strong>{{ $ringkasan['registrasi'] }}</strong>
                <p>Dalam hasil filter saat ini</p>
            </div>

            <div class="report-summary-card">
                <div class="report-summary-top">
                    <span class="report-summary-icon green">
                        <span class="material-symbols-rounded">confirmation_number</span>
                    </span>
                    <span class="report-summary-caption">Tiket digunakan</span>
                </div>
                <strong>{{ $ringkasan['tiket_digunakan'] }}</strong>
                <p>Dalam hasil filter saat ini</p>
            </div>

            <div class="report-summary-card">
                <div class="report-summary-top">
                    <span class="report-summary-icon gold">
                        <span class="material-symbols-rounded">payments</span>
                    </span>
                    <span class="report-summary-caption">Pembayaran berhasil</span>
                </div>
                <strong>{{ $ringkasan['pembayaran'] }}</strong>
                <p>Dalam hasil filter saat ini</p>
            </div>
        </div>

        <div class="report-table-card">
            <div class="report-table-header">
                <div>
                    <h3>Rincian Laporan</h3>
                    <p>
                        Periode
                        {{ \Carbon\Carbon::parse($tanggalMulai)->format('d M Y') }}
                        &ndash;
                        {{ \Carbon\Carbon::parse($tanggalAkhir)->format('d M Y') }}
                    </p>
                </div>

                <span class="report-result-count">
                    {{ $laporan->count() }} data
                </span>
            </div>

            <div class="report-table-responsive">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Tanggal</th>
                            <th>Kode</th>
                            <th>Jenis Laporan</th>
                            <th>Status Registrasi</th>
                            <th>Status Tiket</th>
                            <th>Metode Pembayaran</th>
                            <th>Status Pembayaran</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($laporan as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    {{ \Carbon\Carbon::parse($item['tanggal'])->format('d M Y') }}
                                </td>
                                <td>
                                    <span class="report-code">
                                        {{ $item['kode'] }}
                                    </span>
                                </td>
                                <td>
                                    @if ($item['jenis'] === 'registrasi')
                                        Registrasi
                                    @elseif ($item['jenis'] === 'tiket')
                                        Penggunaan Tiket
                                    @else
                                        Pembayaran
                                    @endif
                                </td>
                                <td>
                                    @if ($item['jenis'] === 'registrasi')
                                        <span class="report-status success">{{ $item['status_registrasi'] }}</span>
                                    @else
                                        <span class="report-status neutral">{{ $item['status_registrasi'] }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="report-status {{ $item['status_tiket'] === 'Sudah Digunakan' ? 'success' : 'neutral' }}">
                                        {{ $item['status_tiket'] }}
                                    </span>
                                </td>
                                <td>{{ $item['metode_pembayaran'] }}</td>
                                <td>
                                    @if ($item['status_pembayaran'] === 'Berhasil')
                                        <span class="report-status success">
                                            Berhasil
                                        </span>
                                    @elseif ($item['status_pembayaran'] === 'Menunggu')
                                        <span class="report-status pending">
                                            Menunggu
                                        </span>
                                    @else
                                        <span class="report-status neutral">
                                            {{ $item['status_pembayaran'] }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="report-empty">
                                    <span class="material-symbols-rounded">search_off</span>
                                    <strong>Data laporan tidak ditemukan</strong>
                                    <p>Coba ubah periode atau jenis laporan.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <p class="report-data-note" style="margin-top:16px;display:flex;align-items:flex-start;gap:8px;">
            <span class="material-symbols-rounded" aria-hidden="true">info</span>
            <span>
                Laporan menampilkan data dari database SI-GORAY. Registrasi dihitung berdasarkan tanggal pembuatan akun,
                tiket digunakan berdasarkan waktu pemindaian gerbang yang berhasil, dan pembayaran berdasarkan
                tanggal transaksi dibuat (bukan tanggal penyelesaian pembayaran).
                File Excel dan PDF mengikuti filter laporan yang dipilih.
            </span>
        </p>

    </div>

<script>
(() => {
    const form = document.querySelector('.report-filter-form');
    const buttons = document.querySelectorAll('[data-report-export]');
    if (!form || !buttons.length) return;

    for (const button of buttons) {
        button.addEventListener('click', async () => {
            if (!form.reportValidity()) return;
            const params = new URLSearchParams(new FormData(form));
            const url = button.dataset.exportUrl + '?' + params.toString();
            buttons.forEach(b => { b.disabled = true; });
            try {
                const response = await fetch(url, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                if (!response.ok) {
                    let message = 'Gagal mengekspor laporan.';
                    try {
                        const data = await response.json();
                        message = data.message || Object.values(data.errors || {}).flat().join(' ') || message;
                    } catch (_) {}
                    window.alert(message);
                    return;
                }
                const disposition = response.headers.get('Content-Disposition') || '';
                const filenameMatch = disposition.match(/filename\*?=(?:UTF-8''|\")?([^\";]+)/i);
                const filename = filenameMatch
                    ? decodeURIComponent(filenameMatch[1].replace(/^\"|\"$/g, '').trim())
                    : `laporan-sigoray.${button.dataset.reportExport === 'excel' ? 'xlsx' : 'pdf'}`;
                const objectUrl = URL.createObjectURL(await response.blob());
                const anchor = document.createElement('a');
                anchor.href = objectUrl;
                anchor.download = filename;
                document.body.appendChild(anchor);
                anchor.click();
                anchor.remove();
                setTimeout(() => URL.revokeObjectURL(objectUrl), 10000);
            } catch (_) {
                window.alert('Tidak dapat terhubung ke server untuk mengekspor laporan.');
            } finally {
                buttons.forEach(b => { b.disabled = false; });
            }
        });
    }
})();
</script>
@endsection