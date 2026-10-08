
@extends('layouts.app')

@section('content')

@include('components.navbar')

<section class="detail-event-section">
    <div class="detail-event-container">

        <!-- Tombol Kembali -->
        <a href="{{ url('/') }}" class="back-event">
            ← Kembali ke Home
        </a>

        <div class="detail-event-card">

            <!-- Gambar Event -->
            <div class="detail-event-image">
                <img
                    src="{{ asset($event['image']) }}"
                    alt="{{ $event['title'] }}"
                >
                <span class="event-category">
                    {{ $event['category'] }}
                </span>
            </div>

            <!-- Informasi Event -->
            <div class="detail-event-info">
                <h1>{{ $event['title'] }}</h1>

                <p class="detail-description">
                    {{ $event['description'] }}
                </p>

                <div class="detail-event-list">
                    <div class="detail-item">
                        <span>📅</span>
                        <div>
                            <strong>Tanggal Event</strong>
                            <p>{{ $event['date'] }}</p>
                        </div>
                    </div>

                    <div class="detail-item">
                        <span>🕒</span>
                        <div>
                            <strong>Waktu</strong>
                            <p>{{ $event['time'] }}</p>
                        </div>
                    </div>

                    <div class="detail-item">
                        <span>📍</span>
                        <div>
                            <strong>Lokasi</strong>
                            <p>{{ $event['location'] }}</p>
                        </div>
                    </div>

                    <div class="detail-item">
                        <span>🎟️</span>
                        <div>
                            <strong>Sisa Tiket</strong>
                            <p>
                                {{ $event['remaining'] }}
                                dari {{ $event['total'] }} tiket
                            </p>
                        </div>
                    </div>
                </div>

                <div class="detail-event-price">
                    <div>
                        <span>Harga Tiket</span>
                        <h2>{{ $event['price'] }}</h2>
                    </div>

                    <a href="#" class="detail-book-button">
                        Pesan Tiket Sekarang
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>

@endsection