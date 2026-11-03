<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingManagementController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'in:REGULER,EVENT'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $query = DB::table('bookings as b')
            ->leftJoin(
                'users as u',
                'u.id',
                '=',
                'b.user_id'
            )
            ->leftJoin(
                'regular_slots as rs',
                'rs.id',
                '=',
                'b.regular_slot_id'
            )
            ->leftJoin(
                'event_schedules as es',
                'es.id',
                '=',
                'b.event_schedule_id'
            )
            ->leftJoin(
                'events as e',
                'e.id',
                '=',
                'es.event_id'
            )
            ->select([
                'b.id',
                'b.jenis_booking',
                'b.status_booking',
                'b.retribusi_diperlukan',
                'b.created_at',
                'u.no_hp_email',
                'rs.tanggal as tanggal_reguler',
                'rs.jam_mulai as jam_reguler',
                'es.tanggal as tanggal_event',
                'es.jam_mulai as jam_event',
                'e.nama_event',
            ]);

        if (!empty($data['q'])) {
            $search = trim($data['q']);

            $query->where(function ($q) use ($search) {
                $q->where(
                    'b.id',
                    'like',
                    '%' . $search . '%'
                )->orWhere(
                    'u.no_hp_email',
                    'like',
                    '%' . $search . '%'
                );
            });
        }

        if (!empty($data['type'])) {
            $query->where(
                'b.jenis_booking',
                $data['type']
            );
        }

        if (!empty($data['status'])) {
            $query->where(
                'b.status_booking',
                $data['status']
            );
        }

        $bookings = $query
            ->orderByDesc('b.created_at')
            ->paginate(15)
            ->withQueryString();

        $statistics = [
            'total' => DB::table('bookings')->count(),
            'regular' => DB::table('bookings')
                ->where('jenis_booking', 'REGULER')
                ->count(),
            'event' => DB::table('bookings')
                ->where('jenis_booking', 'EVENT')
                ->count(),
        ];

        $statuses = DB::table('bookings')
            ->whereNotNull('status_booking')
            ->distinct()
            ->orderBy('status_booking')
            ->pluck('status_booking');

        return view('admin.booking.pemesanan', [
            'bookings' => $bookings,
            'statistics' => $statistics,
            'statuses' => $statuses,
            'filters' => $data,
        ]);
    }
}
