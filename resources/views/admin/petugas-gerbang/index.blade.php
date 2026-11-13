@extends('layouts.admin')

@section('title', 'Kelola Petugas Gerbang')
@section('page', 'Kelola Petugas Gerbang')

@section('content')

    @php
        /*
    |--------------------------------------------------------------------------
    | DUMMY DATA
    |--------------------------------------------------------------------------
    | Data ini hanya untuk preview UI.
    | Nantinya akan diganti dengan data dari backend Yudha.
    */
        $petugas = [
            [
                'id' => 1,
                'nama' => 'Andi Pratama',
                'username' => 'andi.gate',
                'email' => 'andi@example.com',
                'no_hp' => '081234567890',
                'aktif' => true,
            ],
            [
                'id' => 2,
                'nama' => 'Budi Santoso',
                'username' => 'budi.gate',
                'email' => 'budi@example.com',
                'no_hp' => '081298765432',
                'aktif' => true,
            ],
            [
                'id' => 3,
                'nama' => 'Citra Lestari',
                'username' => 'citra.gate',
                'email' => 'citra@example.com',
                'no_hp' => '085712345678',
                'aktif' => false,
            ],
            [
                'id' => 4,
                'nama' => 'Dimas Saputra',
                'username' => 'dimas.gate',
                'email' => 'dimas@example.com',
                'no_hp' => '082112223333',
                'aktif' => true,
            ],
        ];

        $totalPetugas = count($petugas);
        $petugasAktif = count(array_filter($petugas, fn($item) => $item['aktif']));
        $petugasNonaktif = $totalPetugas - $petugasAktif;
    @endphp

    <div class="officer-page">

        {{-- HEADER --}}
        <div class="officer-page-header">
            <div>
                <h1>Kelola Petugas Gerbang</h1>
                <p>
                    Kelola akun dan akses petugas yang bertugas melakukan
                    pemeriksaan tiket di gerbang.
                </p>
            </div>

            <button type="button" class="officer-btn-primary" id="openOfficerModal">
                <span class="material-symbols-rounded">person_add</span>
                Tambah Petugas
            </button>
        </div>

        {{-- STATISTIK --}}
        <div class="officer-stats">

            <div class="officer-stat-card">
                <div class="officer-stat-content">
                    <span>Total Petugas</span>
                    <h2 id="officerTotalCount">{{ $totalPetugas }}</h2>
                    <small>Seluruh akun petugas</small>
                </div>
                <div class="officer-stat-icon">
                    <span class="material-symbols-rounded">groups</span>
                </div>
            </div>

            <div class="officer-stat-card">
                <div class="officer-stat-content">
                    <span>Petugas Aktif</span>
                    <h2 id="officerActiveCount">{{ $petugasAktif }}</h2>
                    <small>Akun dapat digunakan</small>
                </div>
                <div class="officer-stat-icon is-active">
                    <span class="material-symbols-rounded">verified_user</span>
                </div>
            </div>

            <div class="officer-stat-card">
                <div class="officer-stat-content">
                    <span>Petugas Nonaktif</span>
                    <h2 id="officerInactiveCount">{{ $petugasNonaktif }}</h2>
                    <small>Akun tidak dapat digunakan</small>
                </div>
                <div class="officer-stat-icon is-inactive">
                    <span class="material-symbols-rounded">person_off</span>
                </div>
            </div>

        </div>

        {{-- DAFTAR PETUGAS --}}
        <div class="officer-table-card">

            <div class="officer-table-header">
                <div>
                    <h3>Daftar Petugas</h3>
                    <p>Informasi akun petugas gerbang MAJA-TIX.</p>
                </div>
            </div>

            {{-- PENCARIAN DAN FILTER --}}
            <div class="officer-table-toolbar">

                <div class="officer-search">
                    <span class="material-symbols-rounded">search</span>
                    <input type="text" id="officerSearch" placeholder="Cari nama, username, atau email...">
                </div>

                <select id="officerStatusFilter" class="officer-filter">
                    <option value="all">Semua Status</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Nonaktif</option>
                </select>

            </div>

            {{-- TABEL --}}
            <div class="officer-table-wrapper">
                <table class="officer-table">
                    <thead>
                        <tr>
                            <th>Petugas</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Nomor HP</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody id="officerTableBody">
                        @foreach ($petugas as $item)
                            <tr class="officer-row" data-id="{{ $item['id'] }}"
                                data-status="{{ $item['aktif'] ? 'active' : 'inactive' }}">
                                <td>
                                    <div class="officer-identity">
                                        <div class="officer-avatar">
                                            {{ strtoupper(substr($item['nama'], 0, 1)) }}
                                        </div>
                                        <span class="officer-name">{{ $item['nama'] }}</span>
                                    </div>
                                </td>

                                <td class="officer-username">{{ '@' . $item['username'] }}</td>
                                <td class="officer-email">{{ $item['email'] }}</td>
                                <td class="officer-phone">{{ $item['no_hp'] }}</td>

                                <td>
                                    @if ($item['aktif'])
                                        <span class="officer-status active">
                                            <span></span> Aktif
                                        </span>
                                    @else
                                        <span class="officer-status inactive">
                                            <span></span> Nonaktif
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    <div class="officer-actions">

                                        {{-- Detail: tetap mengikuti halaman detail yang sudah dibuat --}}
                                        <a href="{{ route('preview.petugas-gerbang.detail', $item['id']) }}"
                                            class="officer-action-btn" title="Detail petugas">
                                            <span class="material-symbols-rounded">visibility</span>
                                        </a>

                                        {{-- Edit --}}
                                        <button type="button" class="officer-action-btn officer-edit-btn"
                                            title="Edit petugas" data-id="{{ $item['id'] }}"
                                            data-name="{{ $item['nama'] }}" data-username="{{ $item['username'] }}"
                                            data-email="{{ $item['email'] }}" data-phone="{{ $item['no_hp'] }}">
                                            <span class="material-symbols-rounded">edit</span>
                                        </button>

                                        {{-- Aktif / Nonaktif --}}
                                        <button type="button" class="officer-action-btn officer-toggle-btn"
                                            title="{{ $item['aktif'] ? 'Nonaktifkan akun' : 'Aktifkan akun' }}"
                                            aria-label="{{ $item['aktif'] ? 'Nonaktifkan akun' : 'Aktifkan akun' }}">
                                            <span class="material-symbols-rounded">
                                                {{ $item['aktif'] ? 'person_off' : 'person_check' }}
                                            </span>
                                        </button>

                                    </div>
                                </td>
                            </tr>
                        @endforeach

                        <tr id="officerEmptyState" style="display: none;">
                            <td colspan="6" class="officer-empty-state">
                                <span class="material-symbols-rounded">search_off</span>
                                <p>Petugas tidak ditemukan.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="officer-table-footer">
                <span id="officerTableSummary">
                    Menampilkan {{ $totalPetugas }} petugas
                </span>
            </div>
        </div>
    </div>

    {{-- CUSTOM CONFIRMATION AKTIF / NONAKTIF --}}
    <dialog id="officerStatusDialog" class="officer-confirm-dialog" aria-labelledby="officerConfirmTitle"
        aria-describedby="officerConfirmDescription">
        <div class="officer-confirm-content">

            {{-- ICON --}}
            <div class="officer-confirm-icon" id="officerConfirmIcon">
                <span class="material-symbols-rounded">
                    person_check
                </span>
            </div>

            {{-- INFORMASI KONFIRMASI --}}
            <div class="officer-confirm-info">
                <h3 id="officerConfirmTitle">
                    Aktifkan Petugas?
                </h3>

                <p id="officerConfirmDescription">
                    Apakah kamu yakin ingin mengaktifkan petugas ini?
                </p>
            </div>

            {{-- TOMBOL AKSI --}}
            <div class="officer-confirm-actions">

                <button type="button" class="officer-confirm-cancel" id="officerConfirmCancel">
                    Batal
                </button>

                <button type="button" class="officer-confirm-submit activate" id="officerConfirmSubmit">
                    Ya, Aktifkan
                </button>

            </div>

        </div>
    </dialog>

    {{-- MODAL TAMBAH PETUGAS --}}
    <div class="officer-modal-overlay" id="officerModal" aria-hidden="true">

        <div class="officer-modal" role="dialog" aria-modal="true" aria-labelledby="officerModalTitle">

            <div class="officer-modal-header">
                <div>
                    <h2 id="officerModalTitle">Tambah Petugas</h2>
                    <p>Daftarkan akun baru untuk petugas gerbang.</p>
                </div>

                <button type="button" class="officer-modal-close" id="closeOfficerModal">
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>

            <div class="officer-modal-body">

                <div class="officer-form-notice">
                    <span class="material-symbols-rounded">info</span>
                    <p>
                        Akun petugas akan dibuat oleh Admin. Petugas nantinya
                        menggunakan akun terdaftar untuk proses login.
                    </p>
                </div>

                <div class="officer-form-group">
                    <label for="officerName">Nama Lengkap</label>
                    <input type="text" id="officerName" placeholder="Masukkan nama lengkap">
                </div>

                <div class="officer-form-group">
                    <label for="officerUsername">Username</label>
                    <input type="text" id="officerUsername" placeholder="Contoh: andi.gate">
                </div>

                <div class="officer-form-group">
                    <label for="officerEmail">Email</label>
                    <input type="email" id="officerEmail" placeholder="Masukkan email petugas">
                </div>

                <div class="officer-form-group">
                    <label for="officerPhone">Nomor HP</label>
                    <input type="tel" id="officerPhone" placeholder="Contoh: 081234567890">
                </div>
            </div>

            <div class="officer-modal-footer">
                <button type="button" class="officer-btn-secondary" id="cancelOfficerModal">
                    Batal
                </button>

                <button type="button" class="officer-btn-primary" disabled>
                    <span class="material-symbols-rounded">person_add</span>
                    Simpan Petugas
                </button>
            </div>

        </div>

    </div>

    {{-- MODAL EDIT PETUGAS --}}
    <div class="officer-modal-overlay" id="officerEditModal" aria-hidden="true">
        <div class="officer-modal" role="dialog" aria-modal="true" aria-labelledby="officerEditModalTitle">

            <div class="officer-modal-header">
                <div>
                    <h2 id="officerEditModalTitle">Edit Petugas</h2>
                    <p>Perbarui informasi akun petugas gerbang.</p>
                </div>

                <button type="button" class="officer-modal-close" id="closeOfficerEditModal">
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>

            <div class="officer-modal-body">

                <input type="hidden" id="editOfficerId">

                <div class="officer-form-group">
                    <label for="editOfficerName">Nama Lengkap</label>
                    <input type="text" id="editOfficerName" required>
                </div>

                <div class="officer-form-group">
                    <label for="editOfficerUsername">Username</label>
                    <input type="text" id="editOfficerUsername" required>
                </div>

                <div class="officer-form-group">
                    <label for="editOfficerEmail">Email</label>
                    <input type="email" id="editOfficerEmail" required>
                </div>

                <div class="officer-form-group">
                    <label for="editOfficerPhone">Nomor HP</label>
                    <input type="tel" id="editOfficerPhone" required>
                </div>
            </div>

            <div class="officer-modal-footer">
                <button type="button" class="officer-btn-secondary" id="cancelOfficerEditModal">
                    Batal
                </button>

                <button type="button" class="officer-btn-primary" id="saveOfficerEdit">
                    <span class="material-symbols-rounded">save</span>
                    Simpan Perubahan
                </button>
            </div>

        </div>
    </div>

@endsection
