@extends('layouts.admin')

@section('title', 'Tambah Event')

@section('content')
    <div class="event-page">
        <div class="event-page-header">
            <div>
                <span class="event-eyebrow">MANAJEMEN EVENT</span>
                <h1>Tambah Event</h1>
                <p>Lengkapi informasi event, jadwal, dan tarif tiket.</p>
            </div>
            <a href="{{ route('admin.events.index') }}" class="event-btn event-btn-secondary">
                <span class="material-symbols-rounded">arrow_back</span>
                <span>Kembali</span>
            </a>
        </div>

        <form action="{{ route('admin.events.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="event-form-layout">
                <section class="event-form-card">
                    <div class="event-form-heading">
                        <span class="material-symbols-rounded">edit_calendar</span>
                        <div>
                            <h2>Informasi Event</h2>
                            <p>Informasi utama yang ditampilkan kepada pengunjung.</p>
                        </div>
                    </div>

                    <div class="event-form-body">
                        <div class="event-form-field">
                            <label for="name">Nama Event <span>*</span></label>
                            <input id="name" name="name" type="text" value="{{ old('name') }}" maxlength="150"
                                placeholder="Contoh: Turnamen Futsal" required>
                            @error('name')
                                <small class="event-field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="event-form-field">
                            <label for="description">Deskripsi Event</label>
                            <textarea id="description" name="description" rows="4" maxlength="2000"
                                placeholder="Jelaskan informasi singkat event...">{{ old('description') }}</textarea>
                            @error('description')
                                <small class="event-field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="event-form-field">
                            <label for="poster">Poster Event</label>
                            <input id="poster" name="poster" type="file"
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                            <small>Format JPG, PNG, atau WEBP. Maksimal 2 MB.</small>
                            @error('poster')
                                <small class="event-field-error">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </section>

                <section class="event-form-card">
                    <div class="event-form-heading">
                        <span class="material-symbols-rounded">schedule</span>
                        <div>
                            <h2>Jadwal dan Tarif</h2>
                            <p>Atur waktu pelaksanaan dan harga tiket event.</p>
                        </div>
                    </div>

                    <div class="event-form-body">
                        <div class="event-form-field">
                            <label for="date">Tanggal Event <span>*</span></label>
                            <input id="date" name="date" type="date" value="{{ old('date') }}" required>
                            @error('date')
                                <small class="event-field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="event-form-grid">
                            <div class="event-form-field">
                                <label for="start_time">Jam Mulai <span>*</span></label>
                                <input id="start_time" name="start_time" type="time"
                                    value="{{ old('start_time', '08:00') }}" required>
                                @error('start_time')
                                    <small class="event-field-error">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="event-form-field">
                                <label for="end_time">Jam Selesai <span>*</span></label>
                                <input id="end_time" name="end_time" type="time" value="{{ old('end_time', '16:00') }}"
                                    required>
                                @error('end_time')
                                    <small class="event-field-error">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        @error('end_time')
                            <small class="event-field-error">{{ $message }}</small>
                        @enderror

                        {{-- Kuota Tiket --}}
                        <div class="event-form-field">
                            <label for="quota">Kuota Tiket <span>*</span></label>

                            <input id="quota" name="quota" type="number" min="1" step="1"
                                value="{{ old('quota', $defaultQuota ?? '') }}" placeholder="Masukkan kuota tiket"
                                required>

                            <small>
                                Kuota awal diisi otomatis oleh sistem.
                                Admin dapat menyesuaikannya sebelum event disimpan.
                            </small>

                            @error('quota')
                                <small class="event-field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="event-form-field">
                            <label for="tariff">Tarif Non-Warga (Rp)</label>

                            <input type="number" id="tariff" name="tariff" min="0" step="1000"
                                value="{{ old('tariff', 50000) }}" required>

                            @error('tariff')
                                <small class="event-field-error">{{ $message }}</small>
                            @enderror

                            <small>
                                Warga Kota Mojokerto gratis. Tarif ini berlaku
                                untuk pengunjung yang bukan warga Kota Mojokerto.
                            </small>
                        </div>

                        <div class="event-form-notice">
                            Data jadwal dan tarif pada tahap ini masih disimpan sebagai dummy
                            untuk kebutuhan preview.
                        </div>
                    </div>
                </section>
            </div>

            <div class="event-form-actions">
                <a href="{{ route('admin.events.index') }}" class="event-btn event-btn-secondary">
                    Batal
                </a>
                <button type="submit" class="event-btn event-btn-primary">
                    <span class="material-symbols-rounded">save</span>
                    Simpan Event
                </button>
            </div>
        </form>
    </div>
@endsection
