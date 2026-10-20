@extends('layouts.admin')

@section('title', 'Atur Event')

@section('content')
    <div class="event-page">
        <div class="event-page-header">
            <div>
                <span class="event-eyebrow">MANAJEMEN EVENT</span>
                <h1>Atur Event</h1>
                <p>Perbarui informasi, poster, jadwal, dan tarif event.</p>
            </div>
            <a href="{{ route('admin.events.index') }}" class="event-btn event-btn-secondary">
                <span class="material-symbols-rounded">
                    arrow_back
                </span>
                Kembali
            </a>
        </div>

        <form action="{{ route('admin.events.update', $event['id']) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="event-form-layout">
                <section class="event-form-card">
                    <div class="event-form-heading">
                        <span class="material-symbols-rounded">edit_calendar</span>
                        <div>
                            <h2>Informasi Event</h2>
                            <p>Perbarui informasi utama dan poster event.</p>
                        </div>
                    </div>

                    <div class="event-form-body">
                        <div class="event-form-field">
                            <label for="name">Nama Event <span>*</span></label>
                            <input id="name" name="name" type="text" value="{{ old('name', $event['name']) }}"
                                maxlength="150" required>
                            @error('name')
                                <small class="event-field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="event-form-field">
                            <label for="description">Deskripsi Event</label>
                            <textarea id="description" name="description" rows="4" maxlength="2000">{{ old('description', $event['description']) }}</textarea>
                            @error('description')
                                <small class="event-field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        @if (!empty($event['poster']))
                            <div class="event-current-poster">
                                <span>Poster saat ini</span>
                                <img src="{{ asset('storage/' . $event['poster']) }}" alt="Poster {{ $event['name'] }}">
                            </div>
                        @endif

                        <div class="event-form-field">
                            <label for="poster">Ganti Poster (Opsional)</label>
                            <input id="poster" name="poster" type="file"
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                            <small>Biarkan kosong jika poster tidak ingin diganti. Maksimal 2 MB.</small>
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
                            <p>Atur tanggal, waktu, dan harga tiket event.</p>
                        </div>
                    </div>

                    <div class="event-form-body">
                        <div class="event-form-field">
                            <label for="date">Tanggal Event <span>*</span></label>
                            <input id="date" name="date" type="date" value="{{ old('date', $event['date']) }}"
                                required>
                            @error('date')
                                <small class="event-field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="event-form-grid">
                            <div class="event-form-field">
                                <label for="start_time">Jam Mulai <span>*</span></label>
                                <input id="start_time" name="start_time" type="time"
                                    value="{{ old('start_time', $event['start_time']) }}" required>
                                @error('start_time')
                                    <small class="event-field-error">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="event-form-field">
                                <label for="end_time">Jam Selesai <span>*</span></label>
                                <input id="end_time" name="end_time" type="time"
                                    value="{{ old('end_time', $event['end_time']) }}" required>
                                @error('end_time')
                                    <small class="event-field-error">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <div class="event-form-field">
                            <label for="tariff">Tarif Tiket (Rp) <span>*</span></label>
                            <input id="tariff" name="tariff" type="number"
                                value="{{ old('tariff', $event['tariff']) }}" min="0" step="1000" required>
                            <small>Masukkan 0 jika tarif event memang gratis.</small>
                            @error('tariff')
                                <small class="event-field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="event-form-notice">
                            Pengaturan ini masih menggunakan session untuk preview.
                            Integrasi database dilakukan pada tahap backend.
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
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
@endsection
