@extends('layouts.admin')

@section('title', 'Kelola Event')

@section('content')
    <div class="event-page">

        {{-- Header --}}
        <div class="event-page-header">
            <div>
                <span class="event-eyebrow">MANAJEMEN EVENT</span>
                <h1>Kelola Event</h1>
                <p>Kelola informasi, jadwal, dan tarif tiket event GOR.</p>
            </div>

            <a href="{{ route('admin.events.create') }}" class="event-btn event-btn-primary">
                <span class="material-symbols-rounded">add</span>
                Tambah Event
            </a>
        </div>

        {{-- Notifikasi sukses --}}
        @if (session('success'))
            <div class="event-alert-success">
                <span class="material-symbols-rounded">check_circle</span>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        {{-- Notifikasi validasi --}}
        @if ($errors->any())
            <div class="event-alert-error">
                <span class="material-symbols-rounded">error</span>
                <div>
                    <strong>Data belum valid.</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- Informasi tarif --}}
        <div class="event-info-notice">
            <span class="material-symbols-rounded">info</span>
            <div>
                <strong>Informasi Tarif Event</strong>
                <p>
                    Warga Kota Mojokerto mendapatkan tarif gratis.
                    Tarif yang diatur admin berlaku untuk pengunjung non-warga.
                </p>
            </div>
        </div>

        {{-- Ringkasan --}}
        <div class="event-summary-card">
            <div>
                <h2>Total Event</h2>
                <strong>{{ $totalEvents }}</strong>
                <p>Event dalam data preview</p>
            </div>

            <div class="event-summary-icon">
                <span class="material-symbols-rounded">event</span>
            </div>
        </div>

        {{-- Daftar event --}}
        <div class="event-list-card">

            <div class="event-list-header">
                <div>
                    <h2>Daftar Event</h2>
                    <p>Informasi event, jadwal, tarif, dan tiket terjual.</p>
                </div>

                <form method="GET" action="{{ route('admin.events.index') }}" class="event-search-form">
                    <input type="search" name="search" value="{{ $search }}"
                        placeholder="Cari nama atau deskripsi event..." aria-label="Cari event">

                    <button type="submit" class="event-btn event-btn-secondary">
                        <span class="material-symbols-rounded">search</span>
                        Cari
                    </button>

                    @if ($search !== '')
                        <a href="{{ route('admin.events.index') }}" class="event-btn event-btn-secondary">
                            Reset
                        </a>
                    @endif
                </form>
            </div>

            <div class="event-table-wrapper">
                <table class="event-table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Event</th>
                            <th>Jadwal</th>
                            <th>Tarif</th>
                            <th>Tiket Terjual</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($events as $event)
                            <tr>
                                {{-- Nomor --}}
                                <td>{{ $loop->iteration }}</td>

                                {{-- Informasi event --}}
                                <td>
                                    <div class="event-identity">
                                        @if (!empty($event['poster']))
                                            <img src="{{ asset('storage/' . $event['poster']) }}"
                                                alt="Poster {{ $event['name'] }}" class="event-poster-thumb">
                                        @else
                                            <div class="event-poster-placeholder">
                                                <span class="material-symbols-rounded">
                                                    image
                                                </span>
                                            </div>
                                        @endif

                                        <div>
                                            <strong>{{ $event['name'] }}</strong>
                                            <p>
                                                {{ $event['description'] ?: 'Tidak ada deskripsi.' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                {{-- Jadwal --}}
                                <td>
                                    <div class="event-schedule">
                                        <strong>
                                            {{ \Carbon\Carbon::parse($event['date'])->translatedFormat('d M Y') }}
                                        </strong>

                                        <span>
                                            {{ $event['start_time'] }}–{{ $event['end_time'] }} WIB
                                        </span>
                                    </div>
                                </td>

                                {{-- Tarif --}}
                                <td>
                                    <div class="event-tariff-info">
                                        <div>
                                            <span>Warga Kota</span>
                                            <strong class="tariff-free">Gratis</strong>
                                        </div>

                                        <div>
                                            <span>Non-Warga</span>
                                            <strong>
                                                Rp {{ number_format((int) $event['tariff'], 0, ',', '.') }}
                                            </strong>
                                        </div>
                                    </div>
                                </td>

                                {{-- Tiket terjual --}}
                                <td>
                                    <strong>
                                        {{ number_format((int) ($event['sold'] ?? 0), 0, ',', '.') }}
                                    </strong>
                                    <span class="event-sold-label">tiket</span>
                                </td>

                                {{-- Status --}}
                                <td>
                                    @php
                                        $statusClass = match ($event['status'] ?? '') {
                                            'Aktif' => 'event-status-active',
                                            'Mendatang' => 'event-status-upcoming',
                                            default => 'event-status-inactive',
                                        };
                                    @endphp

                                    <span class="event-status {{ $statusClass }}">
                                        {{ $event['status'] ?? 'Tidak diketahui' }}
                                    </span>
                                </td>

                                {{-- Aksi --}}
                                <td>
                                    <a href="{{ route('admin.events.edit', $event['id']) }}" class="event-action-link"
                                        aria-label="Atur event {{ $event['name'] }}">
                                        <span class="material-symbols-rounded">tune</span>
                                        Atur
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="event-empty-state">
                                        <span class="material-symbols-rounded">
                                            event_busy
                                        </span>

                                        <h3>Event tidak ditemukan</h3>

                                        <p>
                                            @if ($search !== '')
                                                Tidak ada event yang cocok dengan
                                                pencarian "{{ $search }}".
                                            @else
                                                Belum ada event yang tersedia.
                                            @endif
                                        </p>

                                        @if ($search !== '')
                                            <a href="{{ route('admin.events.index') }}"
                                                class="event-btn event-btn-secondary">
                                                Reset Pencarian
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Footer tabel --}}
            <div class="event-list-footer">
                <span>
                    Menampilkan {{ count($events) }} dari {{ $totalEvents }} event
                </span>

                <span class="event-preview-label">
                    <span class="material-symbols-rounded">science</span>
                    Mode preview · Dummy data
                </span>
            </div>
        </div>
    </div>
@endsection
