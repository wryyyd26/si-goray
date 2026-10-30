@extends('layouts.admin')

@section('title', 'Kelola User')
@section('page', 'Kelola User')

@section('content')

    <div class="user-page">

        {{-- =========================
        HEADER
    ========================== --}}
        <div class="user-page-header">

            <div>
                <h1>Kelola User</h1>
                <p>
                    Pantau data pengguna yang telah terdaftar
                    pada sistem SI-GORAY.
                </p>
            </div>

        </div>


        {{-- =========================
        STATISTIK USER
    ========================== --}}
        <div class="user-stats">

            {{-- Total User --}}
            <div class="user-stat-card">

                <div class="user-stat-icon">
                    <span class="material-symbols-rounded">
                        group
                    </span>
                </div>

                <div class="user-stat-content">
                    <span>Total User</span>
                    <strong>{{ number_format($totalUsers, 0, ",", ".") }}</strong>
                </div>

            </div>


            {{-- Warga Kota --}}
            <div class="user-stat-card">

                <div class="user-stat-icon">
                    <span class="material-symbols-rounded">
                        location_city
                    </span>
                </div>

                <div class="user-stat-content">
                    <span>Warga Kota</span>

                    <strong>
                        {{ number_format($wargaCount, 0, ",", ".") }}
                    </strong>
                </div>

            </div>


            {{-- Bukan Warga Kota --}}
            <div class="user-stat-card">

                <div class="user-stat-icon">
                    <span class="material-symbols-rounded">
                        person
                    </span>
                </div>

                <div class="user-stat-content">
                    <span>Bukan Warga Kota</span>

                    <strong>
                        {{ number_format($nonWargaCount, 0, ",", ".") }}
                    </strong>
                </div>

            </div>

        </div>


        {{-- =========================
        DATA USER
    ========================== --}}
        <div class="user-table-card">

            {{-- Table Header --}}
            <div class="user-table-header">

                <div class="user-table-title">
                    <h3>Data User</h3>

                    <p>
                        Daftar pengguna yang telah melakukan registrasi.
                    </p>
                </div>


                {{-- Pencarian server-side berbasis kontak/ID, tanpa NIK terenkripsi --}}
                <form method="GET" action="{{ route('admin.user.index') }}" style="display:flex;gap:8px;align-items:center">
                    <input type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="Cari kontak / ID" aria-label="Cari pengguna">
                    <button type="submit" class="btn-outline">Cari</button>
                </form>

                {{-- Search --}}
                <div class="user-search">

                    <span class="material-symbols-rounded">
                        search
                    </span>

                    <input type="text" id="userSearch" placeholder="Filter baris di halaman ini..." autocomplete="off">

                </div>

            </div>


            {{-- Table --}}
            <div class="table-responsive">

                <table class="user-table">

                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NIK</th>
                            <th>Tanggal Lahir</th>
                            <th>Status Warga</th>
                            <th>Terdaftar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>


                    <tbody id="userTableBody">

                        @forelse($users as $index => $user)
                            <tr>

                                {{-- No --}}
                                <td>
                                    {{ $users->firstItem() + $index }}
                                </td>


                                {{-- NIK --}}
                                <td>
                                    <span class="user-nik">
                                        {{ $user['nik_masked'] }}
                                    </span>
                                </td>


                                {{-- Tanggal Lahir --}}
                                <td>
                                    {{ $user['tanggal_lahir_label'] }}
                                </td>


                                {{-- Status Warga --}}
                                <td>

                                    @if ($user['is_warga_kota'] === true)
                                        <span class="user-status warga">
                                            Warga Kota
                                        </span>
                                    @elseif ($user['is_warga_kota'] === false)
                                        <span class="user-status non-warga">
                                            Bukan Warga Kota
                                        </span>
                                    @else
                                        <span class="user-status">Belum diverifikasi</span>
                                    @endif

                                </td>


                                {{-- Tanggal Terdaftar --}}
                                <td>
                                    {{ \Carbon\Carbon::parse($user['created_at'])->format('d M Y') }}
                                </td>


                                {{-- Aksi --}}
                                <td>
                                    <div class="regular-action-group">
                                        <a href="{{ route('admin.user.detail', $user['id']) }}" class="user-action-btn view"
                                            title="Lihat Detail">
                                            <span class="material-symbols-rounded">
                                                visibility
                                            </span>
                                            Detail User
                                        </a>
                                    </div>    
                                </td>
                            </tr>

                        @empty

                            <tr>
                                <td colspan="7" class="user-empty">
                                    <span class="material-symbols-rounded">
                                        group_off
                                    </span>

                                    <span>
                                        Belum ada data user.
                                    </span>
                                </td>
                            </tr>
                        @endforelse

                    </tbody>


            </div>

        </div>

        <div style="margin-top:16px">{{ $users->links() }}</div>
    </div>

@endsection
