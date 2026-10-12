@extends('layouts.admin')

@section('title', 'Atur Kuota Reguler')
@section('page', 'Kelola Kuota Reguler')

@section('content')

    <div class="regular-page">

        {{-- HEADER --}}
        <div class="regular-page-header">
            <div>
                <div class="regular-breadcrumb">
                    Kelola Kuota Reguler
                    <span class="material-symbols-rounded">chevron_right</span>
                    Atur Kuota
                </div>

                <h1>Atur Kuota Harian</h1>

                <p>
                    Atur kapasitas kunjungan reguler untuk tanggal yang dipilih.
                </p>
            </div>
        </div>


        {{-- ALERT --}}
        @if (session('success'))
            <div class="regular-alert regular-alert-success">
                <span class="material-symbols-rounded">check_circle</span>
                <div>
                    <strong>Berhasil</strong>
                    <p>{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="regular-alert regular-alert-error">
                <span class="material-symbols-rounded">error</span>
                <div>
                    <strong>Gagal</strong>
                    <p>{{ session('error') }}</p>
                </div>
            </div>
        @endif


        {{-- MAIN GRID --}}
        <div class="regular-form-layout">

            {{-- FORM --}}
            <div class="dashboard-card regular-form-card">

                <div class="regular-form-card-header">
                    <div class="regular-form-icon">
                        <span class="material-symbols-rounded">
                            tune
                        </span>
                    </div>

                    <div>
                        <h3>Pengaturan Kuota</h3>
                        <p>
                            Tentukan kapasitas pengunjung untuk tanggal ini.
                        </p>
                    </div>
                </div>


                <form action="{{ route('admin.reguler.update', $slot['date']) }}" method="POST"
                    class="regular-setting-form">
                    @csrf
                    @method('PUT')


                    {{-- TANGGAL --}}
                    <div class="regular-form-group">

                        <label>
                            Tanggal Kunjungan
                        </label>

                        <div class="regular-readonly-field">

                            <span class="material-symbols-rounded">
                                calendar_today
                            </span>

                            <div>
                                <strong>
                                    {{ \Carbon\Carbon::parse($slot['date'])->translatedFormat('d F Y') }}
                                </strong>

                                <small>
                                    {{ \Carbon\Carbon::parse($slot['date'])->translatedFormat('l') }}
                                </small>
                            </div>

                        </div>

                        <small class="regular-form-help">
                            Tanggal ditentukan dari daftar kuota harian.
                        </small>

                    </div>

                    {{-- JAM KUNJUNGAN --}}
                    <div class="regular-form-group">

                        <label>
                            Jam Kunjungan
                            <span class="required">*</span>
                        </label>

                        <div class="regular-time-inputs">

                            <div class="regular-time-input">

                                <span class="material-symbols-rounded">
                                    schedule
                                </span>

                                <div>

                                    <small>
                                        Jam Mulai
                                    </small>

                                    <input type="time" name="start_time"
                                        value="{{ old('start_time', $slot['start_time']) }}" required>

                                </div>

                            </div>


                            <div class="regular-time-separator">
                                sampai
                            </div>


                            <div class="regular-time-input">

                                <span class="material-symbols-rounded">
                                    schedule
                                </span>

                                <div>

                                    <small>
                                        Jam Selesai
                                    </small>

                                    <input type="time" name="end_time" value="{{ old('end_time', $slot['end_time']) }}"
                                        required>

                                </div>

                            </div>

                        </div>

                        @error('start_time')
                            <small class="regular-form-error">
                                {{ $message }}
                            </small>
                        @enderror

                        @error('end_time')
                            <small class="regular-form-error">
                                {{ $message }}
                            </small>
                        @enderror

                        <small class="regular-form-help">
                            Jam otomatis mengikuti pengaturan sistem dan dapat
                            disesuaikan oleh admin untuk tanggal ini.
                        </small>

                    </div>


                    {{-- KUOTA --}}
                    <div class="regular-form-group">

                        <label for="quota">
                            Kuota Harian
                            <span class="required">*</span>
                        </label>

                        <div class="regular-input-wrapper">

                            <span class="material-symbols-rounded">
                                group
                            </span>

                            <input type="number" id="quota" name="quota" value="{{ old('quota', $slot['quota']) }}"
                                min="{{ $slot['used'] }}" required>

                            <span class="regular-input-suffix">
                                orang
                            </span>

                        </div>

                        @error('quota')
                            <small class="regular-form-error">
                                {{ $message }}
                            </small>
                        @else
                            <small class="regular-form-help">
                                Kuota tidak boleh lebih kecil dari jumlah pengunjung
                                yang sudah terdaftar.
                            </small>
                        @enderror

                    </div>


                    {{-- STATUS --}}
                    <div class="regular-form-group">

                        <label>
                            Status Tanggal
                            <span class="required">*</span>
                        </label>

                        <div class="regular-status-options">

                            {{-- ACTIVE --}}
                            <label class="regular-status-option">
                                <input type="radio" name="status" value="active"
                                    {{ old('status', $slot['status']) === 'active' ? 'checked' : '' }}>

                                <div class="regular-status-option-content">

                                    <span class="regular-status-option-icon active">
                                        <span class="material-symbols-rounded">
                                            check_circle
                                        </span>
                                    </span>

                                    <div>
                                        <strong>Aktif</strong>
                                        <small>
                                            Pengunjung dapat melakukan booking
                                            pada tanggal ini.
                                        </small>
                                    </div>

                                </div>

                            </label>


                            {{-- INACTIVE --}}
                            <label class="regular-status-option">
                                <input type="radio" name="status" value="inactive"
                                    {{ old('status', $slot['status']) === 'inactive' ? 'checked' : '' }}>

                                <div class="regular-status-option-content">

                                    <span class="regular-status-option-icon inactive">
                                        <span class="material-symbols-rounded">
                                            block
                                        </span>
                                    </span>

                                    <div>
                                        <strong>Nonaktif</strong>
                                        <small>
                                            Pengunjung tidak dapat melakukan
                                            booking pada tanggal ini.
                                        </small>
                                    </div>

                                </div>

                            </label>

                        </div>

                        @error('status')
                            <small class="regular-form-error">
                                {{ $message }}
                            </small>
                        @enderror

                    </div>


                    {{-- ACTION --}}
                    <div class="regular-form-actions">

                        <a href="{{ route('admin.reguler.index') }}" class="regular-btn regular-btn-secondary">
                            <span class="material-symbols-rounded">
                                arrow_back
                            </span>

                            Batal
                        </a>

                        <button type="submit" class="regular-btn regular-btn-primary">
                            <span class="material-symbols-rounded">
                                save
                            </span>

                            Simpan Perubahan
                        </button>

                    </div>

                </form>

            </div>


            {{-- SUMMARY --}}
            <div class="regular-form-sidebar">

                {{-- STATUS CURRENT --}}
                <div class="dashboard-card regular-info-card">

                    <div class="regular-info-header">
                        <span class="material-symbols-rounded">
                            analytics
                        </span>

                        <h3>Ringkasan</h3>
                    </div>


                    <div class="regular-info-date">
                        <span>
                            {{ \Carbon\Carbon::parse($slot['date'])->translatedFormat('d F Y') }}
                        </span>
                    </div>


                    <div class="regular-info-list">

                        <div class="regular-info-row">
                            <span>Kuota</span>
                            <strong>
                                {{ number_format($slot['quota']) }}
                            </strong>
                        </div>

                        <div class="regular-info-row">
                            <span>Terpakai</span>
                            <strong>
                                {{ number_format($slot['used']) }}
                            </strong>
                        </div>

                        <div class="regular-info-row">
                            <span>Sisa</span>

                            <strong>
                                {{ number_format(max($slot['quota'] - $slot['used'], 0)) }}
                            </strong>
                        </div>

                    </div>


                    {{-- PROGRESS --}}
                    @php
                        $percentage = $slot['quota'] > 0 ? min(($slot['used'] / $slot['quota']) * 100, 100) : 0;
                    @endphp

                    <div class="regular-info-progress">

                        <div class="regular-info-progress-header">
                            <span>Penggunaan Kuota</span>
                            <strong>
                                {{ number_format($percentage, 0) }}%
                            </strong>
                        </div>

                        <div class="regular-progress">
                            <div class="regular-progress-bar" style="width: {{ $percentage }}%"></div>
                        </div>

                    </div>

                </div>


                {{-- INFORMATION --}}
                <div class="regular-setting-note">

                    <span class="material-symbols-rounded">
                        info
                    </span>

                    <div>
                        <strong>Perhatian</strong>

                        <p>
                            Jumlah terpakai berasal dari data booking dan tidak
                            dapat diubah melalui halaman ini.
                        </p>

                        <p>
                            Jika tanggal dinonaktifkan, tanggal tetap tersimpan
                            tetapi tidak dapat digunakan untuk booking baru.
                        </p>
                    </div>

                </div>

            </div>

        </div>

    </div>

@endsection
