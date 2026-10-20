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
                <strong>{{ count($users) }}</strong>
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
                    {{ collect($users)->where('is_warga_kota', true)->count() }}
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
                    {{ collect($users)->where('is_warga_kota', false)->count() }}
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


            {{-- Search --}}
            <div class="user-search">

                <span class="material-symbols-rounded">
                    search
                </span>

                <input
                    type="text"
                    id="userSearch"
                    placeholder="Cari berdasarkan NIK..."
                    autocomplete="off"
                >

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
                                {{ $index + 1 }}
                            </td>


                            {{-- NIK --}}
                            <td>
                                <span class="user-nik">
                                    {{ substr($user['nik'], 0, 4) }}
                                    ****
                                    {{ substr($user['nik'], -4) }}
                                </span>
                            </td>


                            {{-- Tanggal Lahir --}}
                            <td>
                                {{ \Carbon\Carbon::parse($user['tanggal_lahir'])->format('d M Y') }}
                            </td>


                            {{-- Status Warga --}}
                            <td>

                                @if($user['is_warga_kota'])

                                    <span class="user-status warga">
                                        Warga Kota
                                    </span>

                                @else

                                    <span class="user-status non-warga">
                                        Bukan Warga Kota
                                    </span>

                                @endif

                            </td>


                            {{-- Tanggal Terdaftar --}}
                            <td>
                                {{ \Carbon\Carbon::parse($user['created_at'])->format('d M Y') }}
                            </td>


                            {{-- Aksi --}}
                            <td>
                                <a href="{{ route('admin.user.detail', $user['id']) }}"
                                    class="user-action-btn view"
                                    title="Lihat Detail"
                                >
                                    <span class="material-symbols-rounded">
                                        visibility
                                    </span>
                                    Detail User
                                </a>
                            </td>
                        </tr>

                    @empty

                        <tr>
                            <td colspan="6" class="user-empty">
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

            </table>

        </div>

    </div>

</div>

@endsection