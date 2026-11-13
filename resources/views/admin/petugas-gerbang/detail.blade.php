@extends('layouts.admin')

@section('title', 'Detail Petugas Gerbang')
@section('page', 'Detail Petugas Gerbang')

@section('content')

    <div class="officer-detail-page">

        {{-- HEADER --}}
        <div class="officer-detail-header">
            <div>
                <a href="{{ route('preview.petugas-gerbang') }}" class="officer-back-link">
                    <span class="material-symbols-rounded">arrow_back</span>
                    Kembali ke Daftar Petugas
                </a>

                <h1>Detail Petugas</h1>
                <p>Informasi lengkap akun petugas gerbang MAJA-TIX.</p>
            </div>

            <span class="officer-detail-id">
                ID Petugas #{{ str_pad($item['id'], 3, '0', STR_PAD_LEFT) }}
            </span>
        </div>

        {{-- PROFILE CARD --}}
        <div class="officer-profile-card">

            <div class="officer-profile-main">

                <div class="officer-profile-avatar">
                    {{ strtoupper(substr($item['nama'], 0, 1)) }}
                </div>

                <div class="officer-profile-identity">
                    <h2>{{ $item['nama'] }}</h2>
                    <p>{{ '@' . $item['username'] }}</p>

                    @if ($item['aktif'])
                        <span class="officer-status active">
                            <span></span> Aktif
                        </span>
                    @else
                        <span class="officer-status inactive">
                            <span></span> Nonaktif
                        </span>
                    @endif
                </div>

            </div>

            <div class="officer-profile-description">
                <span class="material-symbols-rounded">badge</span>
                <div>
                    <strong>Petugas Gerbang</strong>
                    <small>MAJA-TIX Gate</small>
                </div>
            </div>

        </div>

        {{-- INFORMATION --}}
        <div class="officer-detail-grid">

            {{-- PERSONAL INFORMATION --}}
            <div class="officer-detail-card">

                <div class="officer-detail-card-header">
                    <div class="officer-detail-card-icon">
                        <span class="material-symbols-rounded">person</span>
                    </div>

                    <div>
                        <h3>Informasi Pribadi</h3>
                        <p>Informasi identitas petugas.</p>
                    </div>
                </div>

                <div class="officer-detail-list">

                    <div class="officer-detail-item">
                        <span>Nama Lengkap</span>
                        <strong>{{ $item['nama'] }}</strong>
                    </div>

                    <div class="officer-detail-item">
                        <span>Nomor HP</span>
                        <strong>{{ $item['no_hp'] }}</strong>
                    </div>

                </div>

            </div>

            {{-- ACCOUNT INFORMATION --}}
            <div class="officer-detail-card">

                <div class="officer-detail-card-header">
                    <div class="officer-detail-card-icon">
                        <span class="material-symbols-rounded">manage_accounts</span>
                    </div>

                    <div>
                        <h3>Informasi Akun</h3>
                        <p>Informasi akses akun petugas.</p>
                    </div>
                </div>

                <div class="officer-detail-list">

                    <div class="officer-detail-item">
                        <span>Username</span>
                        <strong>{{ '@' . $item['username'] }}</strong>
                    </div>

                    <div class="officer-detail-item">
                        <span>Email</span>
                        <strong>{{ $item['email'] }}</strong>
                    </div>

                    <div class="officer-detail-item">
                        <span>Status Akun</span>

                        @if ($item['aktif'])
                            <strong class="officer-detail-value-active">
                                Aktif
                            </strong>
                        @else
                            <strong class="officer-detail-value-inactive">
                                Nonaktif
                            </strong>
                        @endif
                    </div>

                    <div class="officer-detail-item">
                        <span>Tanggal Pendaftaran</span>
                        <strong>{{ $item['created_at'] }}</strong>
                    </div>

                </div>

            </div>

        </div>

        {{-- ACCOUNT ACCESS INFORMATION --}}
        <div class="officer-access-info">

            <div class="officer-access-icon">
                <span class="material-symbols-rounded">security</span>
            </div>

            <div>
                <h3>Informasi Akses Petugas</h3>

                <p>
                    Akun ini digunakan untuk mengakses aplikasi MAJA-TIX Gate.
                    Petugas melakukan login menggunakan akun yang telah
                    didaftarkan oleh Admin untuk menjalankan proses pemeriksaan
                    tiket pengunjung.
                </p>
            </div>
        </div>
    </div>
@endsection
