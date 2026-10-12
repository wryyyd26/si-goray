@extends('layouts.admin')

@section('title', 'Detail User')
@section('page', 'Detail User')

@section('content')

    <div class="user-detail-page">

        {{-- Header --}}
        <div class="user-detail-page-header">

            <div>
                <a href="{{ route('admin.user.index') }}" class="user-back-link">
                    <span class="material-symbols-rounded">arrow_back</span>
                    Kembali ke Kelola User
                </a>

                <h1>Detail User</h1>
                <p>Informasi lengkap pengguna yang terdaftar pada sistem.</p>
            </div>

        </div>


        {{-- Detail User --}}
        <div class="user-detail-grid">

            {{-- Informasi Utama --}}
            <div class="user-detail-card">

                <div class="user-detail-card-header">
                    <div class="user-detail-card-icon">
                        <span class="material-symbols-rounded">person</span>
                    </div>

                    <div>
                        <h3>Informasi User</h3>
                        <p>Data identitas pengguna</p>
                    </div>
                </div>


                <div class="user-detail-list">

                    {{-- NIK --}}
                    <div class="user-detail-row">
                        <div class="user-detail-label">
                            <span class="material-symbols-rounded">badge</span>
                            NIK
                        </div>

                        <div class="user-detail-value">
                            {{ $user['nik'] }}
                        </div>
                    </div>


                    {{-- Tanggal Lahir --}}
                    <div class="user-detail-row">
                        <div class="user-detail-label">
                            <span class="material-symbols-rounded">calendar_month</span>
                            Tanggal Lahir
                        </div>

                        <div class="user-detail-value">
                            {{ \Carbon\Carbon::parse($user['tanggal_lahir'])->format('d F Y') }}
                        </div>
                    </div>


                    {{-- Status Warga --}}
                    <div class="user-detail-row">
                        <div class="user-detail-label">
                            <span class="material-symbols-rounded">location_city</span>
                            Status Warga
                        </div>

                        <div class="user-detail-value">

                            @if ($user['is_warga_kota'])
                                <span class="user-status warga">
                                    Warga Kota
                                </span>
                            @else
                                <span class="user-status non-warga">
                                    Bukan Warga Kota
                                </span>
                            @endif

                        </div>
                    </div>


                    {{-- Tanggal Terdaftar --}}
                    <div class="user-detail-row">
                        <div class="user-detail-label">
                            <span class="material-symbols-rounded">event</span>
                            Terdaftar
                        </div>

                        <div class="user-detail-value">
                            {{ \Carbon\Carbon::parse($user['created_at'])->format('d F Y') }}
                        </div>
                    </div>

                </div>

            </div>


            {{-- Status --}}
            <div class="user-detail-card">

                <div class="user-detail-card-header">
                    <div class="user-detail-card-icon">
                        <span class="material-symbols-rounded">verified_user</span>
                    </div>

                    <div>
                        <h3>Status User</h3>
                        <p>Status pengguna berdasarkan data registrasi</p>
                    </div>
                </div>


                <div class="user-status-box">

                    <span class="material-symbols-rounded">
                        check_circle
                    </span>

                    <div>
                        <strong>User Terdaftar</strong>
                        <p>
                            Pengguna telah terdaftar pada sistem SI-GORAY.
                        </p>
                    </div>

                </div>

            </div>

        </div>


        {{-- Riwayat --}}
        <div class="user-history-card">

            <div class="user-detail-card-header">
                <div class="user-detail-card-icon">
                    <span class="material-symbols-rounded">history</span>
                </div>

                <div>
                    <h3>Riwayat Aktivitas</h3>
                    <p>Riwayat registrasi pengguna</p>
                </div>
            </div>


            <div class="user-history-empty">

                <span class="material-symbols-rounded">
                    history
                </span>

                <h4>Belum Ada Riwayat</h4>

                <p>
                    Data riwayat registrasi user akan ditampilkan
                    setelah backend terhubung dengan database.
                </p>

            </div>

        </div>

    </div>

@endsection
