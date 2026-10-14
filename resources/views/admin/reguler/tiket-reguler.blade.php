@extends('layouts.admin')

@section('title', 'Kelola Slot Harian')
@section('page', 'Kelola Slot Harian')

@section('content')

    <div class="regular-page">

        <h1>Kelola Slot Harian</h1>
        {{-- HEADER --}}
        <div class="regular-page-header">
            <p>
                Pantau kapasitas tiket reguler dan atur kuota
                harian berdasarkan sesi per 2 jam.
            </p>
        </div>


        {{-- FLASH MESSAGE --}}
        @if (session('success'))
            <div class="regular-toast regular-toast-success">

                <span class="material-symbols-rounded">
                    check_circle
                </span>

                <div>
                    <strong>Berhasil</strong>

                    <p>
                        {{ session('success') }}
                    </p>
                </div>

            </div>
        @endif


        @if (session('error'))
            <div class="regular-toast regular-toast-danger">

                <span class="material-symbols-rounded">
                    error
                </span>

                <div>
                    <strong>Perubahan gagal</strong>

                    <p>
                        {{ session('error') }}
                    </p>
                </div>

            </div>
        @endif


        {{-- SUMMARY --}}
        <div class="regular-summary-grid">

            {{-- TOTAL KUOTA --}}
            <div class="regular-summary-card regular-card-red">

                <div class="regular-summary-top">

                    <span class="regular-summary-icon">
                        <span class="material-symbols-rounded">
                            confirmation_number
                        </span>
                    </span>

                    <span class="regular-summary-label">
                        Total Kuota
                    </span>

                </div>

                <strong>
                    {{ number_format($totalQuota) }}
                </strong>

                <p>
                    Akumulasi kuota hari yang ditampilkan
                </p>

            </div>


            {{-- TERPAKAI --}}
            <div class="regular-summary-card regular-card-green">

                <div class="regular-summary-top">

                    <span class="regular-summary-icon">
                        <span class="material-symbols-rounded">
                            groups
                        </span>
                    </span>

                    <span class="regular-summary-label">
                        Sudah Terpakai
                    </span>

                </div>

                <strong>
                    {{ number_format($totalUsed) }}
                </strong>

                <p>
                    Total pemesanan yang masuk
                </p>

            </div>


            {{-- SISA --}}
            <div class="regular-summary-card regular-card-blue">

                <div class="regular-summary-top">

                    <span class="regular-summary-icon">
                        <span class="material-symbols-rounded">
                            event_available
                        </span>
                    </span>

                    <span class="regular-summary-label">
                        Sisa Kuota
                    </span>

                </div>

                <strong>
                    {{ number_format($totalRemaining) }}
                </strong>

                <p>
                    Kuota yang masih tersedia
                </p>

            </div>

        </div>


        {{-- TABLE CARD --}}
        <div class="regular-table-card">

            {{-- TABLE HEADER --}}
            <div class="regular-table-header">

                <div class="regular-table-title">

                    <div class="regular-title-icon">
                        <span class="material-symbols-rounded">
                            calendar_month
                        </span>
                    </div>

                    <div>

                        <h2>
                            Kuota Harian
                        </h2>

                        <p>
                            Atur kapasitas dan status slot
                            berdasarkan tanggal.
                        </p>

                    </div>

                </div>


                <div class="regular-period-badge">

                    <span class="material-symbols-rounded">
                        calendar_today
                    </span>

                    September 2026

                </div>

            </div>


            {{-- TABLE --}}
            <div class="regular-table-wrapper">

                <table class="regular-table">

                    <thead>

                        <tr>

                            <th>
                                Tanggal
                            </th>

                            <th>
                                Hari
                            </th>

                            <th>
                                Jam
                            </th>

                            <th>
                                Kuota
                            </th>

                            <th>
                                Terpakai
                            </th>

                            <th>
                                Sisa
                            </th>

                            <th>
                                Pengisian
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Aksi
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse ($slots as $slot)
                            @php

                                $date = \Carbon\Carbon::parse($slot['date']);

                                $quota = $slot['quota'];

                                $used = $slot['used'];

                                $remaining = max($quota - $used, 0);

                                $percentage = $quota > 0 ? min(round(($used / $quota) * 100), 100) : 0;

                                /*
                            |--------------------------------------------------------------------------
                            | STATUS TAMPILAN
                            |--------------------------------------------------------------------------
                            */

                                if ($slot['status'] === 'inactive') {
                                    $statusLabel = 'Nonaktif';
                                    $statusClass = 'inactive';
                                } elseif ($remaining <= 0) {
                                    $statusLabel = 'Habis';
                                    $statusClass = 'danger';
                                } elseif ($percentage >= 80) {
                                    $statusLabel = 'Hampir Habis';
                                    $statusClass = 'warning';
                                } else {
                                    $statusLabel = 'Tersedia';
                                    $statusClass = 'success';
                                }

                            @endphp


                            <tr>

                                {{-- TANGGAL --}}
                                <td>

                                    <div class="regular-date">

                                        <strong>
                                            {{ $date->translatedFormat('d M Y') }}
                                        </strong>

                                    </div>

                                </td>


                                {{-- HARI --}}
                                <td>

                                    {{ $date->translatedFormat('l') }}

                                </td>

                                {{-- JAM --}}
                                <td>

                                    <div class="regular-time">

                                        <span class="material-symbols-rounded">
                                            schedule
                                        </span>

                                        <div>

                                            <strong>
                                                {{ \Carbon\Carbon::createFromFormat('H:i', $slot['start_time'])->format('H:i') }}
                                            </strong>

                                            <span>
                                                -
                                                {{ \Carbon\Carbon::createFromFormat('H:i', $slot['end_time'])->format('H:i') }}
                                            </span>

                                        </div>

                                    </div>

                                </td>


                                {{-- KUOTA --}}
                                <td>

                                    <strong>
                                        {{ number_format($quota) }}
                                    </strong>

                                </td>


                                {{-- TERPAKAI --}}
                                <td>

                                    {{ number_format($used) }}

                                </td>


                                {{-- SISA --}}
                                <td>

                                    <strong class="{{ $remaining <= 0 ? 'regular-text-danger' : '' }}">
                                        {{ number_format($remaining) }}
                                    </strong>

                                </td>


                                {{-- PROGRESS --}}
                                <td>

                                    <div class="regular-progress-wrapper">

                                        <div class="regular-progress">

                                            <span class="regular-progress-bar {{ $statusClass }}"
                                                style="width: {{ $percentage }}%;"></span>

                                        </div>

                                        <span>
                                            {{ $percentage }}%
                                        </span>

                                    </div>

                                </td>


                                {{-- STATUS --}}
                                <td>

                                    <span class="regular-status {{ $statusClass }}">

                                        <span class="regular-status-dot"></span>

                                        {{ $statusLabel }}

                                    </span>

                                </td>


                                {{-- AKSI --}}
                                <td>

                                    <a href="{{ route('admin.reguler.edit', $slot['date']) }}" class="regular-action-btn">

                                        <span class="material-symbols-rounded">
                                            tune
                                        </span>

                                        Atur

                                    </a>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="9">

                                    <div class="regular-empty-state">

                                        <span class="material-symbols-rounded">
                                            event_busy
                                        </span>

                                        <strong>
                                            Belum ada data slot
                                        </strong>

                                        <p>
                                            Data kuota harian belum tersedia.
                                        </p>

                                    </div>

                                </td>

                            </tr>
                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

@endsection
