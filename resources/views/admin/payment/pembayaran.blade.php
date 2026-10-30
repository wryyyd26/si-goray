@extends('layouts.admin')

@section('title', 'Kelola Pembayaran')
@section('page', 'Kelola Pembayaran')

@section('content')

    <div class="payment-page">

        {{-- HEADER --}}
        <div class="payment-page-header">
            <div>
                <h1 class="payment-title">Kelola Pembayaran</h1>
                <p class="payment-subtitle">
                    Pantau transaksi pembayaran retribusi pengunjung GOR.
                </p>
            </div>
        </div>


        {{-- STATISTICS --}}
        <div class="payment-stat-grid">

            <div class="payment-stat-card">
                <div class="payment-stat-icon payment-stat-icon-blue">
                    <span class="material-symbols-rounded">payments</span>
                </div>

                <div>
                    <div class="payment-stat-label">Total Pembayaran</div>
                    <div class="payment-stat-value" id="statTotal">{{ number_format($statistics["total"], 0, ",", ".") }}</div>
                    <div class="payment-stat-note">Seluruh transaksi</div>
                </div>
            </div>


            <div class="payment-stat-card">
                <div class="payment-stat-icon payment-stat-icon-green">
                    <span class="material-symbols-rounded">check_circle</span>
                </div>

                <div>
                    <div class="payment-stat-label">Berhasil</div>
                    <div class="payment-stat-value" id="statSuccess">{{ number_format($statistics["by_status"]->get("SUCCESS", 0), 0, ",", ".") }}</div>
                    <div class="payment-stat-note">Pembayaran selesai</div>
                </div>
            </div>


            <div class="payment-stat-card">
                <div class="payment-stat-icon payment-stat-icon-yellow">
                    <span class="material-symbols-rounded">schedule</span>
                </div>

                <div>
                    <div class="payment-stat-label">Menunggu</div>
                    <div class="payment-stat-value" id="statPending">{{ number_format($statistics["by_status"]->get("PENDING", 0), 0, ",", ".") }}</div>
                    <div class="payment-stat-note">Menunggu pembayaran</div>
                </div>
            </div>


            <div class="payment-stat-card">
                <div class="payment-stat-icon payment-stat-icon-red">
                    <span class="material-symbols-rounded">error</span>
                </div>

                <div>
                    <div class="payment-stat-label">Gagal / Kadaluarsa</div>
                    <div class="payment-stat-value" id="statFailed">{{ number_format($statistics["by_status"]->get("FAILED", 0) + $statistics["by_status"]->get("CANCELLED", 0) + $statistics["by_status"]->get("EXPIRED", 0), 0, ",", ".") }}</div>
                    <div class="payment-stat-note">Transaksi tidak selesai</div>
                </div>
            </div>

        </div>


        {{-- FILTER --}}
        <div class="payment-filter-card">

            <div class="payment-filter-header">
                <div>
                    <div class="payment-filter-title">
                        <span class="material-symbols-rounded">filter_alt</span>
                        Filter Pembayaran
                    </div>

                    <div class="payment-filter-description">
                        Cari dan filter transaksi pembayaran.
                    </div>
                </div>
            </div>


            <form id="paymentFilterForm" method="GET" action="{{ route('admin.payment.pembayaran') }}"></form>
            <div class="payment-filter-grid">

                {{-- SEARCH --}}
                <div class="payment-form-group payment-search-group">
                    <label for="paymentSearch">Pencarian</label>

                    <div class="payment-input-icon">
                        <span class="material-symbols-rounded">search</span>

                        <input type="text" id="paymentSearch" name="search" form="paymentFilterForm" value="{{ $filters['search'] }}"
                            placeholder="Kode pembayaran atau ID booking...">
                    </div>
                </div>


                {{-- STATUS --}}
                <div class="payment-form-group">
                    <label for="paymentStatusFilter">Status</label>

                    <select id="paymentStatusFilter" name="status" form="paymentFilterForm" class="payment-form-control">
    <option value="">Semua Status</option>
    @foreach ($statuses as $status)
        <option value="{{ $status }}" @selected($filters['status'] === $status)>
            {{ match($status) {
                'SUCCESS' => 'Berhasil',
                'PENDING' => 'Menunggu',
                'FAILED' => 'Gagal',
                'CANCELLED' => 'Dibatalkan',
                'EXPIRED' => 'Kedaluwarsa',
                default => $status
            } }}
        </option>
    @endforeach
</select>
                </div>


                {{-- METHOD --}}
                <div class="payment-form-group">
                    <label for="paymentMethodFilter">Metode</label>

                    <select id="paymentMethodFilter" name="method" form="paymentFilterForm" class="payment-form-control">
    <option value="">Semua Metode</option>
    @foreach ($methods as $method)
        <option value="{{ $method }}" @selected($filters['method'] === $method)>
            {{ $method }}
        </option>
    @endforeach
</select>
                </div>


                <div class="payment-filter-actions">
                    <a href="{{ route('admin.payment.pembayaran') }}"
   class="payment-btn payment-btn-secondary">
    <span class="material-symbols-rounded">restart_alt</span>
    Reset
</a>
<button type="submit"
        form="paymentFilterForm"
        class="payment-btn payment-btn-primary">
    <span class="material-symbols-rounded">search</span>
    Terapkan
</button>
                </div>

            </div>

        </div>


        {{-- TABLE --}}
        <div class="payment-table-card">

            <div class="payment-table-header">

                <div>
                    <div class="payment-table-title">
                        Daftar Pembayaran
                    </div>

                    <div class="payment-table-description">
                        <span id="paymentResultCount">{{ $payments->total() }}</span> transaksi ditemukan
                    </div>
                </div>

            </div>


            <div class="payment-table-wrapper">

                <table class="payment-table">

                    <thead>
                        <tr>
                            <th>Pembayaran</th>
                            <th>Registrasi</th>
                            <th>Pengunjung</th>
                            <th>Metode</th>
                            <th>Status</th>
                            <th>Expired</th>
                            <th>Gateway Ref.</th>
                            <th class="payment-table-action">Aksi</th>
                        </tr>
                    </thead>

                    <tbody id="paymentTableBody">
    @forelse ($payments as $payment)
        @php
            $status = strtoupper((string) $payment->status_pembayaran);
            $statusLabel = match ($status) {
                'SUCCESS' => 'Berhasil',
                'PENDING' => 'Menunggu',
                'FAILED' => 'Gagal',
                'CANCELLED' => 'Dibatalkan',
                'EXPIRED' => 'Kedaluwarsa',
                default => $status
            };
            $statusClass = match ($status) {
                'SUCCESS' => 'success',
                'PENDING' => 'pending',
                default => 'failed'
            };
        @endphp
        <tr>
            <td>
                <strong>{{ $payment->kode_bayar }}</strong>
                <div>
                    Rp {{ number_format($payment->nominal, 0, ',', '.') }}
                </div>
            </td>
            <td>
                <code>{{ $payment->booking_id }}</code>
            </td>
            <td>
                <span title="Nama pengunjung belum tersedia pada query">
                    -
                </span>
            </td>
            <td>
                {{ $payment->metode_pembayaran }}
            </td>
            <td>
                <span class="payment-status {{ $statusClass }}">
                    {{ $statusLabel }}
                </span>
            </td>
            <td>
                {{ $payment->expired_at
                    ? \Carbon\Carbon::parse($payment->expired_at)->format('d/m/Y H:i')
                    : '-' }}
            </td>
            <td>
                <code>{{ $payment->referensi_gateway ?? '-' }}</code>
            </td>
            <td>
                <a class="payment-action"
                   href="{{ route('admin.payment.show', ['payment' => $payment->id]) }}">
                    <span class="material-symbols-rounded">visibility</span>
                    Detail
                </a>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="8" style="text-align:center;padding:24px">
                Belum ada transaksi pembayaran yang sesuai filter.
            </td>
        </tr>
    @endforelse
</tbody>

                </table>
                <div style="padding:18px 0">
                    {{ $payments->links() }}
                </div>

            </div>

        </div>

    </div>
@endsection
