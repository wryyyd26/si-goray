<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\VisitorNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisitorNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = (string) $request->user('web')->getAuthIdentifier();
        $base = VisitorNotification::query()->where('user_id', $userId);

        $rows = (clone $base)->latest()->limit(30)->get([
            'id', 'category', 'title', 'message', 'icon', 'read_at', 'created_at',
        ]);

        return response()->json([
            'unread_count' => (clone $base)->whereNull('read_at')->count(),
            'notifications' => $rows->map(fn ($item) => [
                'id' => (string) $item->id,
                'category' => $item->category,
                'title' => $item->title,
                'message' => $item->message,
                'icon' => $item->icon,
                'is_read' => $item->read_at !== null,
                'created_at' => $item->created_at?->toIso8601String(),
            ])->all(),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function markRead(Request $request, string $notification): JsonResponse
    {
        $id = (string) $request->user('web')->getAuthIdentifier();
        $row = VisitorNotification::query()->where('user_id', $id)->findOrFail($notification);
        if ($row->read_at === null) {
            $row->forceFill(['read_at' => now()])->save();
        }
        return response()->json(['message' => 'Notifikasi ditandai dibaca.']);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        VisitorNotification::query()
            ->where('user_id', (string) $request->user('web')->getAuthIdentifier())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Semua notifikasi ditandai dibaca.']);
    }
}
