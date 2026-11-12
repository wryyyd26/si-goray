@extends('layouts.admin')

@section('title', 'Semua Notifikasi')
@section('page', 'Notifikasi')

@section('content')
    <div class="report-page">
        <div class="report-page-header">
            <div>
                <h1>Semua Notifikasi</h1>
                <p>Aktivitas booking, pembayaran, dan pesan pengunjung SI-GORAY.</p>
            </div>
            <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                @csrf
                <button type="submit" class="notification-mark-all">Tandai semua dibaca</button>
            </form>
        </div>

        <div class="report-table-card">
            <div class="notification-list" style="max-height:none">
                @forelse ($notifications as $notification)
                    <div class="notification-item {{ $notification->read_at ? '' : 'is-unread' }}">
                        <div class="notification-item-icon">
                            <span class="material-symbols-rounded">{{ $notification->icon }}</span>
                        </div>
                        <div class="notification-item-content">
                            <h4 class="notification-item-title">{{ $notification->title }}</h4>
                            <p class="notification-item-message">{{ $notification->message }}</p>
                            <span class="notification-item-time">{{ $notification->created_at?->diffForHumans() }}</span>
                            <form method="POST" action="{{ route('admin.notifications.read', ['notification' => $notification->id]) }}" style="margin-top:8px">
                                @csrf
                                <button type="submit" class="notification-mark-all">Buka detail</button>
                            </form>
                        </div>
                        @if (! $notification->read_at)
                            <span class="notification-unread-indicator" aria-label="Belum dibaca"></span>
                        @endif
                    </div>
                @empty
                    <div class="notification-empty"><p>Belum ada notifikasi.</p></div>
                @endforelse
            </div>
            <div style="padding:16px">{{ $notifications->links() }}</div>
        </div>
    </div>
@endsection
