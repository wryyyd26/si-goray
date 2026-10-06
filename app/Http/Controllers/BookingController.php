<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingRequest;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
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
}