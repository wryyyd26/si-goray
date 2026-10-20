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
                    <div class="payment-stat-value" id="statTotal">0</div>
                    <div class="payment-stat-note">Seluruh transaksi</div>
                </div>
            </div>


            <div class="payment-stat-card">
                <div class="payment-stat-icon payment-stat-icon-green">
                    <span class="material-symbols-rounded">check_circle</span>
                </div>

                <div>
                    <div class="payment-stat-label">Berhasil</div>
                    <div class="payment-stat-value" id="statSuccess">0</div>
                    <div class="payment-stat-note">Pembayaran selesai</div>
                </div>
            </div>


            <div class="payment-stat-card">
                <div class="payment-stat-icon payment-stat-icon-yellow">
                    <span class="material-symbols-rounded">schedule</span>
                </div>

                <div>
                    <div class="payment-stat-label">Menunggu</div>
                    <div class="payment-stat-value" id="statPending">0</div>
                    <div class="payment-stat-note">Menunggu pembayaran</div>
                </div>
            </div>


            <div class="payment-stat-card">
                <div class="payment-stat-icon payment-stat-icon-red">
                    <span class="material-symbols-rounded">error</span>
                </div>

                <div>
                    <div class="payment-stat-label">Gagal / Kadaluarsa</div>
                    <div class="payment-stat-value" id="statFailed">0</div>
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


            <div class="payment-filter-grid">

                {{-- SEARCH --}}
                <div class="payment-form-group payment-search-group">
                    <label for="paymentSearch">Pencarian</label>

                    <div class="payment-input-icon">
                        <span class="material-symbols-rounded">search</span>

                        <input type="text" id="paymentSearch"
                            placeholder="Kode pembayaran, registrasi, atau pengunjung...">
                    </div>
                </div>


                {{-- STATUS --}}
                <div class="payment-form-group">
                    <label for="paymentStatusFilter">Status</label>

                    <select id="paymentStatusFilter" class="payment-form-control">
                        <option value="">Semua Status</option>
                        <option value="pending">Pending</option>
                        <option value="berhasil">Berhasil</option>
                        <option value="gagal">Gagal</option>
                        <option value="kadaluarsa">Kadaluarsa</option>
                    </select>
                </div>


                {{-- METHOD --}}
                <div class="payment-form-group">
                    <label for="paymentMethodFilter">Metode</label>

                    <select id="paymentMethodFilter" class="payment-form-control">
                        <option value="">Semua Metode</option>
                        <option value="qris">QRIS</option>
                        <option value="va">Virtual Account</option>
                    </select>
                </div>


                <div class="payment-filter-actions">
                    <button type="button" class="payment-btn payment-btn-secondary" id="btnResetPaymentFilter">
                        <span class="material-symbols-rounded">restart_alt</span>
                        Reset
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
                        <span id="paymentResultCount">0</span> transaksi ditemukan
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
                    </tbody>

                </table>

            </div>

        </div>

    </div>
@endsection
