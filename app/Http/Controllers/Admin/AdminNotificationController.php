<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminNotificationController extends Controller
{
    public function feed(): JsonResponse
    {
        return response()->json([
            'unread_count' => AdminNotification::query()->whereNull('read_at')->count(),
            'notifications' => AdminNotification::query()
                ->latest('created_at')
                ->limit(15)
                ->get()
                ->map(fn (AdminNotification $n) => [
                    'id' => $n->id,
                    'icon' => $n->icon,
                    'title' => $n->title,
                    'message' => $n->message,
                    'time' => $n->created_at?->diffForHumans() ?? '',
                    'url' => $n->url,
                    'isRead' => $n->read_at !== null,
                ])->values(),
        ]);
    }

    public function index(): View
    {
        return view('admin.notifications.index', [
            'notifications' => AdminNotification::query()->latest('created_at')->paginate(30),
        ]);
    }

    public function read(Request $request, AdminNotification $notification): JsonResponse|RedirectResponse
    {
        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Notifikasi ditandai dibaca.']);
        }

        return redirect()->to($notification->url);
    }

    public function readAll(Request $request): JsonResponse|RedirectResponse
    {
        AdminNotification::query()->whereNull('read_at')->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Semua notifikasi ditandai dibaca.']);
        }

        return back();
    }
}
