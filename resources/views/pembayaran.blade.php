@extends('layouts.app')

@section('title', 'Pembayaran Tiket | SI-GORAY')

@section('content')
@php
    $pemesan = $detail ?? [];

    $namaPemesan = $pemesan['nama_lengkap'] ?? 'Pengunjung SI-GORAY';
    $namaEvent = $pemesan['event_title'] ?? 'Event SI-GORAY';
    $deskripsiEvent = $pemesan['event_description'] ?? 'Informasi event SI-GORAY';
    $gambarEvent = $pemesan['event_image'] ?? asset('images/events/futsal.jpg');

    $tanggalEvent = $pemesan['event_date'] ?? 'Jadwal belum tersedia';
    $waktuEvent = $pemesan['event_time'] ?? 'Waktu belum tersedia';
    $lokasiEvent = $pemesan['event_location'] ?? 'GOR A. Yani, Kota Mojokerto';

    $jenis = $jenisPemesan ?? 'warga-kota';

    $jenisLabel = $jenis === 'luar-kota'
        ? 'Pengunjung luar kota'
        : 'Warga Kota Mojokerto';

    $subtotal = (float) ($subtotal ?? $pemesan['subtotal'] ?? 0);
    $biayaLayanan = (float) ($biayaLayanan ?? $pemesan['biaya_layanan'] ?? 0);
    $total = (float) ($total ?? $pemesan['total_pembayaran'] ?? ($subtotal + $biayaLayanan));
@endphp

<main class="payment-page">

    {{-- HEADER --}}
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

    {{-- JUDUL HALAMAN --}}
    <section class="payment-intro">
        <div>
            <span class="payment-eyebrow">CHECKOUT TIKET</span>
            <h1>Selesaikan pembayaranmu</h1>
            <p>Periksa kembali detail pesanan sebelum melanjutkan.</p>
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

    {{-- PESAN STATUS --}}
    @if (session('error'))
        <div class="payment-alert" role="alert">
            <span class="material-symbols-rounded">error</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if (session('success'))
        <div class="payment-success" role="status">
            <span class="material-symbols-rounded">check_circle</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="payment-alert" role="alert">
            <span class="material-symbols-rounded">error</span>
            <div>
                <strong>Periksa kembali data berikut:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="payment-layout">

        {{-- KOLOM KIRI: RINGKASAN PESANAN --}}
        <section class="order-column">
            <article class="order-card">

                {{-- POSTER DAN IDENTITAS EVENT --}}
                <div class="event-cover">
                    @if (!empty($gambarEvent))
                        <img
                            src="{{ $gambarEvent }}"
                            alt="Poster {{ $namaEvent }}"
                            onerror="this.style.display='none'">
                    @endif

                    <div class="event-cover-overlay">
                        <span class="event-tag">TIKET EVENT</span>
                        <h2>{{ $namaEvent }}</h2>
                        <p>{{ $deskripsiEvent }}</p>
                    </div>
                </div>

                <div class="order-card-body">

                    <div class="order-heading">
                        <div>
                            <span class="order-kicker">RINGKASAN PESANAN</span>
                            <h2>{{ $namaEvent }}</h2>
                        </div>

                        <span class="order-icon material-symbols-rounded">
                            confirmation_number
                        </span>
                    </div>

                    {{-- NAMA PEMESAN --}}
                    <div class="order-info-row">
                        <span class="material-symbols-rounded">person</span>
                        <div>
                            <small>Nama pemesan</small>
                            <strong>{{ $namaPemesan }}</strong>
                        </div>
                    </div>

                    {{-- NIK --}}
                    @if (!empty($pemesan['nik']))
                        <div class="order-info-row">
                            <span class="material-symbols-rounded">badge</span>
                            <div>
                                <small>NIK</small>
                                <strong>{{ $pemesan['nik'] }}</strong>
                            </div>
                        </div>
                    @endif

                    {{-- EMAIL --}}
                    @if (!empty($pemesan['email']))
                        <div class="order-info-row">
                            <span class="material-symbols-rounded">mail</span>
                            <div>
                                <small>Email</small>
                                <strong>{{ $pemesan['email'] }}</strong>
                            </div>
                        </div>
                    @endif

                    {{-- NOMOR HP --}}
                    @if (!empty($pemesan['nomor_hp']))
                        <div class="order-info-row">
                            <span class="material-symbols-rounded">call</span>
                            <div>
                                <small>Nomor HP</small>
                                <strong>{{ $pemesan['nomor_hp'] }}</strong>
                            </div>
                        </div>
                    @endif

                    {{-- JADWAL EVENT --}}
                    <div class="order-info-row">
                        <span class="material-symbols-rounded">calendar_month</span>
                        <div>
                            <small>Tanggal event</small>
                            <strong>{{ $tanggalEvent }}</strong>
                        </div>
                    </div>

                    <div class="order-info-row">
                        <span class="material-symbols-rounded">schedule</span>
                        <div>
                            <small>Waktu event</small>
                            <strong>{{ $waktuEvent }}</strong>
                        </div>
                    </div>

                    <div class="order-info-row">
                        <span class="material-symbols-rounded">location_on</span>
                        <div>
                            <small>Lokasi</small>
                            <strong>{{ $lokasiEvent }}</strong>
                        </div>
                    </div>

                    {{-- KATEGORI PEMESAN --}}
                    <div class="order-info-row">
                        <span class="material-symbols-rounded">groups</span>
                        <div>
                            <small>Kategori pemesan</small>
                            <strong>{{ $jenisLabel }}</strong>
                        </div>
                    </div>

                    {{-- RINCIAN HARGA --}}
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
                <p>Pastikan informasi pemesan dan nominal pembayaran sudah benar.</p>
            </div>
        </section>

        {{-- KOLOM KANAN: METODE PEMBAYARAN --}}
        <section class="checkout-column">

            @if ($total > 0)
                <form
                    method="POST"
                    action="{{ route('pembayaran.midtrans') }}"
                    id="midtrans-payment-form"
                    class="checkout-card">

                    @csrf

                    <input
                        type="hidden"
                        name="jenis_pemesan"
                        value="{{ $jenis }}"
                    >

                    <div class="checkout-heading">
                        <span class="checkout-icon material-symbols-rounded">
                            account_balance_wallet
                        </span>

                        <div>
                            <h2>Metode pembayaran</h2>
                            <p>Pilih metode pembayaran yang tersedia.</p>
                        </div>
                    </div>

                    <div class="payment-method-options">

                        {{-- TRANSFER BANK --}}
                        <label class="payment-method-option">
                            <input
                                type="radio"
                                name="metode_pembayaran"
                                value="bank_transfer"
                                {{ old('metode_pembayaran', 'bank_transfer') === 'bank_transfer' ? 'checked' : '' }}
                                required
                            >

                            <span class="method-icon bank-icon material-symbols-rounded">
                                account_balance
                            </span>

                            <span class="method-copy">
                                <strong>Transfer bank</strong>
                                <small>
                                    Bank yang tersedia mengikuti konfigurasi Midtrans.
                                </small>
                            </span>

                            <span class="method-radio"></span>
                        </label>

                        {{-- QRIS --}}
                        <label class="payment-method-option">
                            <input
                                type="radio"
                                name="metode_pembayaran"
                                value="qris"
                                {{ old('metode_pembayaran') === 'qris' ? 'checked' : '' }}
                                required
                            >

                            <span class="method-icon qris-icon material-symbols-rounded">
                                qr_code_2
                            </span>

                            <span class="method-copy">
                                <strong>QRIS</strong>
                                <small>
                                    Bayar menggunakan aplikasi yang mendukung QRIS.
                                </small>
                            </span>

                            <span class="method-radio"></span>
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
                        <p>
                            Transaksi hanya dianggap berhasil setelah status
                            pembayaran diverifikasi oleh backend.
                        </p>
                    </div>
                </form>
            @else
                {{-- TIKET GRATIS --}}
                <div class="checkout-card free-ticket-card">
                    <div class="checkout-heading">
                        <span class="checkout-icon material-symbols-rounded">
                            confirmation_number
                        </span>

                        <div>
                            <h2>Tiket gratis</h2>
                            <p>Tidak ada pembayaran yang perlu dilakukan.</p>
                        </div>
                    </div>

                    <div class="checkout-total">
                        <span>Total pembayaran</span>
                        <strong>GRATIS</strong>
                    </div>

                    <form
                        method="POST"
                        action="{{ route('pembayaran.konfirmasi') }}">

                        @csrf

                        <input
                            type="hidden"
                            name="jenis_pemesan"
                            value="{{ $jenis }}"
                        >

                        <button type="submit" class="payment-button">
                            <span>Lanjutkan pemesanan</span>
                            <span class="material-symbols-rounded">arrow_forward</span>
                        </button>
                    </form>

                    <div class="checkout-security">
                        <span class="material-symbols-rounded">info</span>
                        <p>
                            Pemesanan belum dianggap selesai sampai backend
                            mengonfirmasi dan mencatat pemesanan.
                        </p>
                    </div>
                </div>
            @endif

            <div class="checkout-note">
                <span class="material-symbols-rounded">support_agent</span>
                <div>
                    <strong>Butuh bantuan?</strong>
                    <p>
                        Periksa kembali detail pesanan sebelum melanjutkan.
                    </p>
                </div>
            </div>

        </section>
    </div>

    {{-- FOOTER --}}
    <footer class="payment-footer">
        <span>© {{ date('Y') }} SI-GORAY</span>
        <span>Informasi pembayaran dan pesanan tiket</span>
    </footer>

</main>
@endsection
