@extends('layouts.admin')

@section('title', 'Atur Slot Reguler')
@section('page', 'Tiket Reguler')

@section('content')
    @php
        // old() dipakai agar draft tetap tampil jika validasi server menemukan kesalahan.
        $formSessions = old('sessions', $selectedSlots->toArray());
    @endphp

    <div class="regular-page regular-config-page">
        <div class="regular-page-header regular-config-page-header">
            <div>
                <span class="regular-config-eyebrow">MANAJEMEN TIKET REGULER</span>
                <h1>Atur Slot Reguler</h1>
                <p>Kelola tarif dan seluruh sesi kunjungan untuk satu tanggal.</p>
            </div>
            <a href="{{ route('admin.reguler.index', ['month' => $monthKey, 'date' => $date]) }}"
                class="regular-btn regular-btn-secondary regular-config-back">
                <span class="material-symbols-rounded">arrow_back</span>
                Kembali ke Kalender
            </a>
        </div>

        @if (session('success'))
            <div class="regular-alert regular-alert-success" role="status">
                <span class="material-symbols-rounded">check_circle</span>
                <div><strong>Berhasil</strong>
                    <p>{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="regular-alert regular-alert-error" role="alert">
                <span class="material-symbols-rounded">error</span>
                <div><strong>Perubahan gagal</strong>
                    <p>{{ session('error') }}</p>
                </div>
            </div>
        @endif

        <div class="regular-config-date-card">
            <div class="regular-config-date-icon">
                <span class="material-symbols-rounded">calendar_month</span>
            </div>
            <div>
                <span class="regular-config-overline">TANGGAL YANG DIATUR</span>
                <h2>{{ $dateLabel }}</h2>
                <p>Jam operasional default sistem adalah 08.00–20.00 WIB dalam enam sesi.</p>
            </div>
        </div>

        <form action="{{ route('admin.reguler.update', ['date' => $date]) }}" method="POST" id="regularConfigurationForm"
            class="regular-config-form">
            @csrf
            @method('PUT')
            <input type="hidden" name="month" value="{{ $monthKey }}">

            <section class="regular-config-card" aria-labelledby="regularConfigTariffTitle">
                <div class="regular-config-card-heading">
                    <div class="regular-config-card-icon is-price">
                        <span class="material-symbols-rounded">payments</span>
                    </div>
                    <div>
                        <h2 id="regularConfigTariffTitle">Pengaturan Tarif Tiket</h2>
                        <p>Tarif berlaku untuk seluruh sesi reguler pada tanggal ini.</p>
                    </div>
                </div>

                <div class="regular-config-tariff-grid">
                    <div class="regular-config-field is-locked">
                        <div class="regular-config-field-label-row">
                            <label for="warga_price">Tarif Warga Kota</label>
                            <span class="regular-config-lock-badge">
                                <span class="material-symbols-rounded">lock</span>
                                Terkunci
                            </span>
                        </div>
                        <div class="regular-config-input-with-prefix is-readonly">
                            <span>Rp</span>
                            <input type="number" id="warga_price" value="0" readonly aria-readonly="true"
                                tabindex="-1">
                        </div>
                        <small>Tarif warga kota ditetapkan gratis oleh sistem.</small>
                    </div>

                    <div class="regular-config-field">
                        <div class="regular-config-field-label-row">
                            <label for="non_warga_price">Tarif Non-Warga Kota</label>
                        </div>
                        <div class="regular-config-input-with-prefix">
                            <span>Rp</span>
                            <input type="number" id="non_warga_price" name="non_warga_price" min="1" step="500"
                                value="{{ old('non_warga_price', $nonWargaPrice) }}" placeholder="Masukkan tarif" required>
                        </div>
                        <small>Tarif ini berlaku sama untuk seluruh sesi pada tanggal yang dipilih.</small>
                        @error('non_warga_price')
                            <small class="regular-config-error">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </section>

            <section class="regular-config-card" aria-labelledby="regularConfigSessionsTitle">
                <div class="regular-config-card-heading regular-config-sessions-heading">
                    <div class="regular-config-heading-copy">
                        <div class="regular-config-card-icon is-session">
                            <span class="material-symbols-rounded">schedule</span>
                        </div>
                        <div>
                            <h2 id="regularConfigSessionsTitle">Pengaturan Sesi</h2>
                            <p>Ubah jam, kuota, atau status. Sesi tambahan akan ikut disimpan bersama perubahan lainnya.</p>
                        </div>
                    </div>
                    <button type="button" class="regular-btn regular-btn-primary" id="addRegularSession">
                        <span class="material-symbols-rounded">add</span>
                        Tambah Sesi
                    </button>
                </div>

                <div class="regular-config-default-note">
                    <span class="material-symbols-rounded">info</span>
                    <p>Sistem menyediakan enam sesi otomatis, mulai pukul 08.00 sampai 20.00 WIB. Admin dapat menambahkan
                        sesi di luar jadwal default bila diperlukan.</p>
                </div>

                @error('sessions')
                    <div class="regular-config-validation-summary" role="alert">{{ $message }}</div>
                @enderror

                <div class="regular-config-session-list" id="regularConfigSessionList">
                    @foreach ($formSessions as $index => $session)
                        @php
                            $sessionIsNew = filter_var($session['is_new'] ?? false, FILTER_VALIDATE_BOOLEAN);
                            $sessionUsed = (int) ($session['used'] ?? 0);
                            $sessionNumber = (int) ($session['session_number'] ?? $index + 1);
                        @endphp
                        <article class="regular-config-session-card" data-session-card>
                            <div class="regular-config-session-card-header">
                                <div>
                                    <span class="regular-config-session-kicker">SESI {{ $sessionNumber }}</span>
                                    <h3 data-session-title>{{ $sessionIsNew ? 'Sesi tambahan' : 'Pengaturan sesi' }}</h3>
                                </div>
                                <span class="regular-config-session-used">
                                    <span class="material-symbols-rounded">confirmation_number</span>
                                    Terpakai: {{ number_format($sessionUsed, 0, ',', '.') }}
                                </span>
                            </div>

                            <input type="hidden" name="sessions[{{ $index }}][id]"
                                value="{{ $session['id'] ?? '' }}">
                            <input type="hidden" name="sessions[{{ $index }}][is_new]"
                                value="{{ $sessionIsNew ? 1 : 0 }}">
                            <input type="hidden" name="sessions[{{ $index }}][session_number]"
                                value="{{ $sessionNumber }}" data-session-number>
                            <input type="hidden" value="{{ $sessionUsed }}" data-session-used>

                            <div class="regular-config-session-fields">
                                <div class="regular-config-field">
                                    <label for="session_{{ $index }}_start">Jam Mulai</label>
                                    <input type="time" id="session_{{ $index }}_start"
                                        name="sessions[{{ $index }}][start_time]"
                                        value="{{ $session['start_time'] ?? '' }}" required>
                                    @error('sessions.' . $index . '.start_time')
                                        <small class="regular-config-error">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="regular-config-field">
                                    <label for="session_{{ $index }}_end">Jam Selesai</label>
                                    <input type="time" id="session_{{ $index }}_end"
                                        name="sessions[{{ $index }}][end_time]"
                                        value="{{ $session['end_time'] ?? '' }}" required>
                                    @error('sessions.' . $index . '.end_time')
                                        <small class="regular-config-error">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="regular-config-field">
                                    <label for="session_{{ $index }}_quota">Kuota Sesi</label>
                                    <input type="number" id="session_{{ $index }}_quota"
                                        name="sessions[{{ $index }}][quota]" min="{{ $sessionUsed }}"
                                        value="{{ $session['quota'] ?? 0 }}" required>
                                    <small>Minimal sama dengan jumlah tiket terpakai.</small>
                                    @error('sessions.' . $index . '.quota')
                                        <small class="regular-config-error">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="regular-config-field">
                                    <label for="session_{{ $index }}_status">Status Sesi</label>
                                    <select id="session_{{ $index }}_status"
                                        name="sessions[{{ $index }}][status]" required>
                                        <option value="active"
                                            {{ ($session['status'] ?? 'active') === 'active' ? 'selected' : '' }}>Aktif
                                        </option>
                                        <option value="inactive"
                                            {{ ($session['status'] ?? 'active') === 'inactive' ? 'selected' : '' }}>
                                            Nonaktif</option>
                                    </select>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <div class="regular-config-savebar">
                <div class="regular-config-savebar-copy">
                    <strong>Periksa kembali pengaturanmu</strong>
                    <span>Perubahan tarif dan seluruh sesi disimpan sekaligus.</span>
                </div>
                <div class="regular-config-savebar-actions">
                    <a href="{{ route('admin.reguler.index', ['month' => $monthKey, 'date' => $date]) }}"
                        class="regular-btn regular-btn-secondary">Batalkan</a>
                    <button type="submit" class="regular-btn regular-btn-primary" id="saveRegularConfiguration">
                        <span class="material-symbols-rounded">save</span>
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
