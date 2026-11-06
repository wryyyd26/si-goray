@extends('layouts.app')

@section('title', 'Login Pengunjung - SI-GORAY')

@section('content')
<main id="visitor-login" data-next="{{ $next }}" data-request-url="{{ route('pengunjung.web-auth.request-otp') }}" data-verify-url="{{ route('pengunjung.web-auth.verify-otp') }}" style="max-width:460px;margin:64px auto;padding:24px;font-family:inherit;">
    <h1>Login Pengunjung</h1>
    <p>Masukkan email untuk menerima kode OTP. Kode berlaku selama 5 menit.</p>

    <form id="visitor-login-form" novalidate>
        @csrf
        <div style="margin:20px 0;">
            <label for="visitor-email" style="display:block;margin-bottom:8px;">Email</label>
            <input id="visitor-email" type="email" autocomplete="email" required maxlength="255" placeholder="nama@email.com" style="display:block;width:100%;box-sizing:border-box;padding:12px;" />
        </div>

        <div id="visitor-otp-section" hidden style="margin:20px 0;">
            <label for="visitor-otp" style="display:block;margin-bottom:8px;">Kode OTP (6 angka)</label>
            <input id="visitor-otp" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" minlength="6" maxlength="6" placeholder="123456" style="display:block;width:100%;box-sizing:border-box;padding:12px;" />
            <p style="font-size:0.9em;">Belum menerima kode? <button id="visitor-resend" type="button">Kirim ulang OTP</button> (jeda pengiriman 60 detik).</p>
        </div>

        <p id="visitor-message" role="status" aria-live="polite"></p>
        <button id="visitor-submit" type="submit" style="padding:12px 20px;cursor:pointer;">Kirim Kode OTP</button>
    </form>

    <p><a href="{{ $next }}">Kembali ke halaman sebelumnya</a></p>
</main>
<script src="{{ asset('js/pengunjung-login.js') }}" defer></script>
@endsection
