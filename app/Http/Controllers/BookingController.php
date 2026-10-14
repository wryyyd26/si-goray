<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingRequest;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class BookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService
    ) {
    }

    /**
     * Membuat booking baru.
     */
    public function store(BookingRequest $request): JsonResponse
    {
        try {
            $booking = $this->bookingService->createBooking(
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Booking berhasil dibuat.',
                'data' => $booking,
            ], 201);

        } catch (InvalidArgumentException $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Menampilkan riwayat booking milik user.
     *
     * Untuk sementara user_id dikirim melalui
     * query parameter.
     *
     * Setelah authentication selesai,
     * user_id akan diambil dari user yang sedang login.
     */
    public function history(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => [
                'required',
                'uuid',
                'exists:users,id',
            ],
        ]);

        $bookings = $this->bookingService->getBookingHistory(
            $request->user_id
        );

        return response()->json([
            'success' => true,
            'message' => 'Riwayat booking berhasil diambil.',
            'data' => $bookings,
        ]);
    }
}