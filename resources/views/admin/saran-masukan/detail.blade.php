
@extends('layouts.admin')

@section('title', 'Detail Saran & Masukan')
@section('page', 'Saran & Masukan')

@section('content')
<div class="sm-page">
    <a href="{{ route('admin.saran-masukan.index') }}" class="sm-back-link">
        <span class="material-symbols-rounded">arrow_back</span>
        Kembali ke daftar pesan
    </a>

    <div class="sm-detail-heading">
        <div>
            <h1>Detail Pesan</h1>
            <p>Informasi lengkap pesan yang dikirim pengguna.</p>
        </div>
        <span class="sm-status {{ $saranMasukan['status'] }}">
            {{ ucfirst($saranMasukan['status']) }}
        </span>
    </div>

    <div class="sm-detail-grid">
        <section class="sm-detail-card">
            <div class="sm-detail-card-heading">
                <span class="material-symbols-rounded">mail</span>
                <div>
                    <h3>Informasi Pesan</h3>
                    <p>Isi pesan yang diterima SI-GORAY.</p>
                </div>
            </div>

            <div class="sm-info-row">
                <span>Kategori</span>
                <strong>{{ $saranMasukan['category'] === 'feedback' ? 'Feedback' : 'Reservasi' }}</strong>
            </div>
            <div class="sm-info-row">
                <span>Subjek</span>
                <strong>{{ $saranMasukan['subject'] }}</strong>
            </div>
            <div class="sm-info-row">
                <span>Tanggal dikirim</span>
                <strong>{{ \Carbon\Carbon::parse($saranMasukan['created_at'])->format('d M Y, H:i') }}</strong>
            </div>

            <div class="sm-message-box">
                <span>Isi Pesan</span>
                <p>{{ $saranMasukan['message'] }}</p>
            </div>
        </section>

        <aside class="sm-detail-card">
            <div class="sm-detail-card-heading">
                <span class="material-symbols-rounded">person</span>
                <div>
                    <h3>Informasi Pengirim</h3>
                    <p>Informasi kontak pengguna.</p>
                </div>
            </div>

            <div class="sm-contact-item">
                <span class="material-symbols-rounded">person</span>
                <div>
                    <span>Nama Lengkap</span>
                    <strong>{{ $saranMasukan['name'] }}</strong>
                </div>
            </div>

            <div class="sm-contact-item">
                <span class="material-symbols-rounded">mail</span>
                <div>
                    <span>Email</span>
                    <strong>{{ $saranMasukan['email'] }}</strong>
                </div>
            </div>

            <div class="sm-contact-item">
                <span class="material-symbols-rounded">call</span>
                <div>
                    <span>WhatsApp</span>
                    <strong>{{ $saranMasukan['whatsapp'] }}</strong>
                </div>
            </div>

            <div class="sm-demo-note">
                <span class="material-symbols-rounded">info</span>
                <p>Status ini masih data dummy. Perubahan status belum disimpan.</p>
            </div>
        </aside>
    </div>
</div>
@endsection