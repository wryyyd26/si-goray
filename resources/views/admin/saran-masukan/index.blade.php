@extends('layouts.admin')

@section('title', 'Saran & Masukan')
@section('page', 'Saran & Masukan')

@section('content')
    <div class="sm-page">
        <div class="sm-heading">
            <div>
                <h1>Saran & Masukan</h1>
                <p>Kelola feedback dan pesan reservasi dari pengguna SI-GORAY.</p>
            </div>
        </div>

        <div class="sm-stats">
            <div class="sm-stat">
                <div class="sm-stat-icon sm-icon-total">
                    <span class="material-symbols-rounded">forum</span>
                </div>
                <div><span>Total Pesan</span><strong>{{ $ringkasan['total'] }}</strong></div>
            </div>
            <div class="sm-stat">
                <div class="sm-stat-icon sm-icon-baru">
                    <span class="material-symbols-rounded">mark_email_unread</span>
                </div>
                <div><span>Pesan Baru</span><strong>{{ $ringkasan['baru'] }}</strong></div>
            </div>
            <div class="sm-stat">
                <div class="sm-stat-icon sm-icon-proses">
                    <span class="material-symbols-rounded">pending_actions</span>
                </div>
                <div><span>Diproses</span><strong>{{ $ringkasan['diproses'] }}</strong></div>
            </div>
            <div class="sm-stat">
                <div class="sm-stat-icon sm-icon-selesai">
                    <span class="material-symbols-rounded">task_alt</span>
                </div>
                <div><span>Selesai</span><strong>{{ $ringkasan['selesai'] }}</strong></div>
            </div>
        </div>

        <div class="sm-table-card">
            <div class="sm-table-heading">
                <div>
                    <h3>Daftar Pesan Masuk</h3>
                    <p>Data contoh untuk review tampilan dan alur admin.</p>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.saran-masukan.index') }}" class="sm-filter">
                <div class="sm-search">
                    <span class="material-symbols-rounded">search</span>
                    <input type="search" name="search" value="{{ request('search') }}"
                        placeholder="Cari nama, email, atau subjek...">
                </div>

                <select name="category" aria-label="Filter kategori">
                    <option value="">Semua Kategori</option>
                    <option value="feedback" @selected(request('category') === 'feedback')>Feedback</option>
                    <option value="reservasi" @selected(request('category') === 'reservasi')>Reservasi</option>
                </select>

                <select name="status" aria-label="Filter status">
                    <option value="">Semua Status</option>
                    <option value="baru" @selected(request('status') === 'baru')>Baru</option>
                    <option value="diproses" @selected(request('status') === 'diproses')>Diproses</option>
                    <option value="selesai" @selected(request('status') === 'selesai')>Selesai</option>
                </select>

                <button type="submit" class="sm-btn sm-btn-primary">
                    <span class="material-symbols-rounded">filter_alt</span>
                    Filter
                </button>

                <a href="{{ route('admin.saran-masukan.index') }}" class="sm-btn sm-btn-light">Reset</a>
            </form>

            <div class="sm-table-wrap">
                <table class="sm-table">
                    <thead>
                        <tr>
                            <th>Pengirim</th>
                            <th>Kategori</th>
                            <th>Subjek</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pesan as $item)
                            <tr>
                                <td>
                                    <div class="sm-sender">
                                        <div class="sm-avatar">
                                            {{ strtoupper(substr($item['name'], 0, 1)) }}
                                        </div>
                                        <div>
                                            <strong>{{ $item['name'] }}</strong>
                                            <span>{{ $item['email'] }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="sm-category {{ $item['category'] }}">
                                        {{ $item['category'] === 'feedback' ? 'Feedback' : 'Reservasi' }}
                                    </span>
                                </td>
                                <td class="sm-subject">{{ $item['subject'] }}</td>
                                <td>{{ \Carbon\Carbon::parse($item['created_at'])->format('d M Y, H:i') }}</td>
                                <td>
                                    <span class="sm-status {{ $item['status'] }}">
                                        {{ ucfirst($item['status']) }}
                                    </span>
                                </td>

                                {{-- 1. KOLOM AKSI PADA TABEL --}}
                                <td>
                                    <div class="sm-action-group">
                                        <a href="{{ route('admin.saran-masukan.detail', $item['id']) }}"
                                            class="sm-action-btn sm-action-btn-detail">
                                            <span>Detail</span>
                                            <span class="material-symbols-rounded">arrow_forward</span>
                                        </a>

                                        {{-- Aksi Hapus --}}
                                        <form action=""
                                            method="POST" class="sm-delete-form"
                                            onsubmit="return confirm('Yakin ingin menghapus pesan ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="sm-action-btn sm-action-btn-delete"
                                                aria-label="Hapus Pesanan">
                                                <span class="material-symbols-rounded">delete</span>
                                                <span>Hapus</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="sm-empty">
                                        <span class="material-symbols-rounded">inbox</span>
                                        <strong>Pesan tidak ditemukan</strong>
                                        <p>Coba ubah kata kunci atau filter pencarian.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="sm-pagination">
                {{ $pesan->links() }}
            </div>
        </div>
    </div>
@endsection
