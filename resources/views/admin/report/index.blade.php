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
                <button type="button" class="report-export-btn excel" disabled title="Ekspor Excel belum diaktifkan">
                    <span class="material-symbols-rounded">table_view</span>
                    Ekspor Excel
                </button>

                <button type="button" class="report-export-btn pdf" disabled title="Ekspor PDF belum diaktifkan">
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

            <form action="{{ route('admin.report.index') }}" method="GET" class="report-filter-form">

                <div class="report-field">
                    <label for="tanggal_mulai">Tanggal Mulai</label>
                    <input type="date" id="tanggal_mulai" name="tanggal_mulai" value="{{ $tanggalMulai }}"
                        max="{{ $tanggalAkhir }}" required>
                </div>

                <div class="report-field">
                    <label for="tanggal_akhir">Tanggal Akhir</label>
                    <input type="date" id="tanggal_akhir" name="tanggal_akhir" value="{{ $tanggalAkhir }}"
                        min="{{ $tanggalMulai }}" required>
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
                        –
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
                                    <span class="report-status success">
                                        {{ $item['status_registrasi'] }}
                                    </span>
                                </td>
                                <td>
                                    <span
                                        class="report-status
                                    {{ $item['status_tiket'] === 'Sudah Digunakan' ? 'success' : 'pending' }}">
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
                                            Tidak Ada
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

        <p class="report-demo-note">
            <span class="material-symbols-rounded">info</span>
            Data saat ini merupakan simulasi untuk review UI. Angka belum
            mencerminkan data operasional SI-GORAY yang sebenarnya.
        </p>

    </div>
@endsection
