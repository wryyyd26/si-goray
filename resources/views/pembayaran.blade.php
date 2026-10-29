
@extends('layouts.app')

@section('title', 'Pembayaran Tiket | SI-GORAY')

@section('content')
@php
    $pemesan = $detail ?? [];
    $namaPemesan = $pemesan['nama_lengkap'] ?? 'Pengunjung SI-GORAY';
    $jenisLabel = ($jenisPemesan ?? 'warga-kota') === 'luar-kota'
        ? 'Pengunjung luar kota'
        : 'Warga kota';
@endphp

<main class="payment-page">
    <header class="payment-header">
        <a href="{{ route('home') }}" class="payment-brand">
            <span class="payment-brand-mark">
                <span class="material-symbols-rounded">confirmation_number</span>
            </span>
            <span>
                <strong>SI-GORAY</strong>
                <small>Sistem Informasi Gelanggang Olahraga</small>
            </span>
        </a>

        <div class="payment-secure">
            <span class="material-symbols-rounded">verified_user</span>
            <span>Pembayaran aman</span>
        </div>
    </header>

    <section class="payment-intro">
        <div>
            <span class="payment-eyebrow">CHECKOUT TIKET</span>
            <h1>Selesaikan pembayaranmu</h1>
            <p>Periksa detail pesanan dan pilih metode pembayaran yang tersedia.</p>
        </div>

        <div class="payment-steps">
            <span class="payment-step completed">
                <span class="material-symbols-rounded">check</span>
                Pesanan
            </span>
            <span class="payment-step active">
                <span class="material-symbols-rounded">payments</span>
                Pembayaran
            </span>
        </div>
    </section>

    @if (session('error'))
        <div class="payment-alert">
            <span class="material-symbols-rounded">error</span>
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="payment-alert">
            <span class="material-symbols-rounded">error</span>
            Periksa kembali data yang dimasukkan.
        </div>
    @endif

    <div class="payment-layout">
        {{-- KOLOM KIRI: DETAIL PESANAN --}}
        <section class="order-column">
            <article class="order-card">
                <div class="event-cover">
                    <img
                        src="{{ asset('images/events/futsal.jpg') }}"
                        alt="Ilustrasi acara olahraga SI-GORAY"
                        onerror="this.style.display='none'">

                    <div class="event-cover-overlay">
                        <span class="event-tag">TIKET OLAHRAGA</span>
                        <h2>SI-GORAY</h2>
                        <p>Pengalaman olahraga lebih mudah.</p>
                    </div>
                </div>

                <div class="order-card-body">
                    <div class="order-heading">
                        <div>
                            <span class="order-kicker">RINGKASAN PESANAN</span>
                            <h2>Tiket Gelanggang Olahraga</h2>
                        </div>
                        <span class="order-icon material-symbols-rounded">
                            confirmation_number
                        </span>
                    </div>

                    <div class="order-info-row">
                        <span class="material-symbols-rounded">person</span>
                        <div>
                            <small>Nama pemesan</small>
                            <strong>{{ $namaPemesan }}</strong>
                        </div>
                    </div>

                    @if (!empty($pemesan['nik']))
                        <div class="order-info-row">
                            <span class="material-symbols-rounded">badge</span>
                            <div>
                                <small>NIK</small>
                                <strong>{{ $pemesan['nik'] }}</strong>
                            </div>
                        </div>
                    @endif

                    @if (!empty($pemesan['email']))
                        <div class="order-info-row">
                            <span class="material-symbols-rounded">mail</span>
                            <div>
                                <small>Email</small>
                                <strong>{{ $pemesan['email'] }}</strong>
                            </div>
                        </div>
                    @endif

                    @if (!empty($pemesan['nomor_hp']))
                        <div class="order-info-row">
                            <span class="material-symbols-rounded">call</span>
                            <div>
                                <small>Nomor HP</small>
                                <strong>{{ $pemesan['nomor_hp'] }}</strong>
                            </div>
                        </div>
                    @endif

                    <div class="order-info-row">
                        <span class="material-symbols-rounded">location_on</span>
                        <div>
                            <small>Kategori pemesan</small>
                            <strong>{{ $jenisLabel }}</strong>
                        </div>
                    </div>

                    <div class="order-price-breakdown">
                        <div>
                            <span>Subtotal tiket</span>
                            <strong>Rp {{ number_format($subtotal, 0, ',', '.') }}</strong>
                        </div>
                        <div>
                            <span>Biaya layanan</span>
                            <strong>Rp {{ number_format($biayaLayanan, 0, ',', '.') }}</strong>
                        </div>
                    </div>

                    <div class="order-total">
                        <div>
                            <span>Total pembayaran</span>
                            <small>Termasuk biaya layanan</small>
                        </div>
                        <strong>Rp {{ number_format($total, 0, ',', '.') }}</strong>
                    </div>
                </div>
            </article>

            <div class="payment-help">
                <span class="material-symbols-rounded">info</span>
                <p>Pastikan informasi pemesan sudah benar sebelum melanjutkan pembayaran.</p>
            </div>
        </section>

        {{-- KOLOM KANAN: METODE PEMBAYARAN --}}
        <section class="checkout-column">
            <form method="POST"
                  action="{{ route('pembayaran.midtrans') }}"
                  id="midtrans-payment-form"
                  class="checkout-card">
                @csrf

                <input type="hidden"
                       name="jenis_pemesan"
                       value="{{ $jenisPemesan ?? 'warga-kota' }}">

                <div class="checkout-heading">
                    <span class="checkout-icon material-symbols-rounded">account_balance_wallet</span>
                    <div>
                        <h2>Metode pembayaran</h2>
                        <p>Pilih metode yang kamu inginkan.</p>
                    </div>
                </div>

                <div class="payment-method-options">
                    <label class="payment-method-option">
                        <input type="radio"
                               name="metode_pembayaran"
                               value="bank_transfer"
                               checked
                               required>

                        <span class="method-icon bank-icon material-symbols-rounded">
                            account_balance
                        </span>

                        <span class="method-copy">
                            <strong>Transfer bank</strong>
                            <small>BCA, BNI, BRI, Mandiri, dan lainnya sesuai ketersediaan gateway.</small>
                        </span>

                        <span class="method-radio"></span>
                    </label>

                    <label class="payment-method-option">
                        <input type="radio"
                               name="metode_pembayaran"
                               value="qris"
                               required>

                        <span class="method-icon qris-icon material-symbols-rounded">
                            qr_code_2
                        </span>

                        <span class="method-copy">
                            <strong>QRIS</strong>
                            <small>Bayar dengan aplikasi yang mendukung QRIS.</small>
                        </span>

                        <span class="method-radio"></span>
                    </label>

                    <label class="payment-method-option method-unavailable">
                        <input type="radio"
                               name="metode_pembayaran"
                               value="ewallet"
                               disabled>

                        <span class="method-icon wallet-icon material-symbols-rounded">
                            account_balance_wallet
                        </span>

                        <span class="method-copy">
                            <strong>E-Wallet</strong>
                            <small>Belum diaktifkan pada konfigurasi pembayaran saat ini.</small>
                        </span>

                        <span class="method-badge">Segera hadir</span>
                    </label>
                </div>

                <div class="checkout-total">
                    <span>Total yang dibayar</span>
                    <strong>Rp {{ number_format($total, 0, ',', '.') }}</strong>
                </div>

                <button type="submit" class="payment-button">
                    <span>Lanjutkan pembayaran</span>
                    <span class="material-symbols-rounded">arrow_forward</span>
                </button>

                <div class="checkout-security">
                    <span class="material-symbols-rounded">lock</span>
                    <p>Pembayaran diproses melalui Midtrans. Status transaksi diperbarui setelah verifikasi.</p>
                </div>
            </form>

            <div class="checkout-note">
                <span class="material-symbols-rounded">support_agent</span>
                <div>
                    <strong>Butuh bantuan?</strong>
                    <p>Pastikan detail pesanan dan nominal sudah sesuai sebelum membayar.</p>
                </div>
            </div>
        </section>
    </div>

    <footer class="payment-footer">
        <span>© {{ date('Y') }} SI-GORAY</span>
        <span>Informasi pembayaran dan pesanan tiket</span>
    </footer>
</main>
@endsection
