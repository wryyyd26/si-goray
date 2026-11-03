@extends('layouts.app')

@section('content')

@include('components.navbar')

@php
    // Jadwal pertama hanya digunakan sebagai ringkasan tampilan.
    // Pemilihan event_schedule_id untuk booking harus dilakukan secara eksplisit.
    $jadwalRingkasan = collect($eventData['schedules'] ?? [])->first();
    $tanggalRingkasan = $jadwalRingkasan['date'] ?? null;
    $jamMulaiRingkasan = $jadwalRingkasan['start_time'] ?? null;
    $jamSelesaiRingkasan = $jadwalRingkasan['end_time'] ?? null;
    $jumlahJadwal = count($eventData['schedules'] ?? []);
    $deskripsiParagraf = collect(preg_split('/\R\s*\R/u', trim((string) ($eventData['description'] ?? ''))))->filter(fn ($teks) => trim($teks) !== '');
@endphp

<main class="event-detail-page">

    {{-- DETAIL EVENT --}}
    <section class="event-detail">

        {{-- POSTER EVENT --}}
        <div class="event-main-image">
            <img
                src="{{ $eventData['image'] ?? asset('images/events/futsal.jpg') }}"
                alt="Poster {{ $eventData['title'] ?? 'Event' }}">
        </div>

        {{-- PANEL TIKET --}}
        <aside class="event-ticket-panel">

            <div class="ticket-summary">
                <h2>{{ $eventData['title'] ?? 'Event' }}</h2>
                <p>{{ $eventData['category'] ?? 'Event' }}</p>

                <ul class="event-meta">
                    <li>
                        <span class="material-symbols-rounded">calendar_month</span>
                        <span>
                            @if ($tanggalRingkasan)
                                {{ \Carbon\Carbon::parse($tanggalRingkasan)->locale('id')->translatedFormat('l, d F Y') }}
                                @if ($jumlahJadwal > 1)
                                    (jadwal terdekat; tersedia {{ $jumlahJadwal }} jadwal)
                                @endif
                            @else
                                Jadwal belum tersedia
                            @endif
                        </span>
                    </li>

                    <li>
                        <span class="material-symbols-rounded">schedule</span>
                        <span>
                            @if ($jamMulaiRingkasan)
                                {{ $jamMulaiRingkasan }}@if ($jamSelesaiRingkasan) - {{ $jamSelesaiRingkasan }}@endif WIB
                            @else
                                Jam belum tersedia
                            @endif
                        </span>
                    </li>

                    <li>
                        <span class="material-symbols-rounded">location_on</span>
                        <span>GOR A. Yani</span>
                    </li>

                    <li>
                        <span class="material-symbols-rounded">confirmation_number</span>
                        <span>Kuota Event: Sisa {{ (int) ($eventData['remaining'] ?? 0) }} dari {{ (int) ($eventData['total'] ?? 0) }} Tiket</span>
                    </li>
                </ul>

                <div class="ticket-quota">
                    <span></span>
                </div>

                {{-- HARGA TIKET WARGA --}}
                <div class="ticket-price-option">
                    <button
                        type="button"
                        class="citizen-button active"
                        data-category="warga"
                        aria-pressed="true">
                        Gratis
                    </button>

                    <span>NIK Kota Mojokerto</span>
                </div>

                {{-- HARGA TIKET LUAR KOTA --}}
                <div class="ticket-price-option">
                    <button
                        type="button"
                        class="non-citizen-button"
                        data-category="luar"
                        aria-pressed="false">
                        {{ $eventData['price'] ?? 'Tarif belum tersedia' }}
                    </button>

                    <span>NIK Luar Kota Mojokerto</span>
                </div>
            </div>

            {{-- PEMILIHAN KATEGORI --}}
            <div class="ticket-bottom">

                <img
                    src="{{ asset('storage/images/Logo-Si-goray.png') }}" alt="Logo SI-GORAY" class="ticket-brand-logo">

                <strong>PESAN TIKET EVENT</strong>

                <a
                    href="{{ route('events.pesan-tiket', ['event' => $eventData['id']]) }}"
                    class="ticket-category-button active">
                    Warga Kota Mojokerto
                </a>

                <a
                    href="{{ route('events.pesan-tiket-luar', ['event' => $eventData['id']]) }}"
                    class="ticket-category-button">
                    Luar Warga Kota Mojokerto
                </a>

                <small>Pilih Sesuai NIK Wilayah</small>

            </div>

        </aside>

    </section>

    {{-- DESKRIPSI EVENT --}}
    <section class="event-description">

        <h2>Deskripsi Event</h2>

        @forelse ($deskripsiParagraf as $paragraf)
            @if (trim($paragraf) !== '')
                <p>{{ $paragraf }}</p>
            @endif
        @empty
            <p>Deskripsi event belum tersedia.</p>
        @endforelse

    </section>

</main>

{{-- JAVASCRIPT PEMILIHAN KATEGORI TIKET --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const categoryButtons = document.querySelectorAll(
            '.event-detail-page [data-category]'
        );

        categoryButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const selectedCategory = this.dataset.category;

                categoryButtons.forEach(function(item) {
                    const isActive =
                        item.dataset.category === selectedCategory;

                    item.classList.toggle('active', isActive);
                    item.setAttribute('aria-pressed', String(isActive));
                });
            });
        });
    });
</script>

@include('components.footer')

@endsection
