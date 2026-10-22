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
     * Membuat booking baru milik user login.
     */
    public function store(BookingRequest $request): JsonResponse
    {
        $data = $request->validated();

        // ID akun berasal dari Sanctum, bukan frontend.
        $data['user_id'] = $request->user()->id;

        try {
            $booking = $this->bookingService
                ->createBooking($data);

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
     * Riwayat booking milik user login.
     */
    public function history(Request $request): JsonResponse
    {
        $bookings = $this->bookingService
            ->getBookingHistory(
                $request->user()->id
            );

        return response()->json([
            'success' => true,
            'message' => 'Riwayat booking berhasil diambil.',
            'data' => $bookings,
        ]);
    }
}
