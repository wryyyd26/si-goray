<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BookingService
{
    /**
     * Membuat booking baru dengan status DRAFT.
     *
     * Tahap ini menangani aturan dasar:
     * - REGULER harus memiliki regular_slot_id
     * - EVENT harus memiliki event_schedule_id
     * - Hanya salah satu jadwal yang boleh digunakan
     */
    public function createBooking(array $data): Booking
    {
        return DB::transaction(function () use ($data) {

            $jenisBooking = strtoupper($data['jenis_booking'] ?? '');

            if (!in_array($jenisBooking, ['REGULER', 'EVENT'], true)) {
                throw new InvalidArgumentException(
                    'Jenis booking harus REGULER atau EVENT.'
                );
            }

            /*
             * Booking REGULER
             */
            if ($jenisBooking === 'REGULER') {

                if (empty($data['regular_slot_id'])) {
                    throw new InvalidArgumentException(
                        'regular_slot_id wajib diisi untuk booking REGULER.'
                    );
                }

                if (!empty($data['event_schedule_id'])) {
                    throw new InvalidArgumentException(
                        'event_schedule_id harus kosong untuk booking REGULER.'
                    );
                }

                $regularSlotId = $data['regular_slot_id'];
                $eventScheduleId = null;
            }

            /*
             * Booking EVENT
             */
            else {

                if (empty($data['event_schedule_id'])) {
                    throw new InvalidArgumentException(
                        'event_schedule_id wajib diisi untuk booking EVENT.'
                    );
                }

                if (!empty($data['regular_slot_id'])) {
                    throw new InvalidArgumentException(
                        'regular_slot_id harus kosong untuk booking EVENT.'
                    );
                }

                $regularSlotId = null;
                $eventScheduleId = $data['event_schedule_id'];
            }

            /*
             * Membuat booking.
             *
             * Status awal sengaja DRAFT karena
             * booking belum menyelesaikan seluruh proses.
             */
            return Booking::create([
                'user_id' => $data['user_id'],
                'identity_id' => $data['identity_id'] ?? null,

                'jenis_booking' => $jenisBooking,

                'regular_slot_id' => $regularSlotId,
                'event_schedule_id' => $eventScheduleId,

                'tariff_id' => $data['tariff_id'],

                'retribusi_diperlukan' =>
                    $data['retribusi_diperlukan'] ?? false,

                'status_booking' => 'DRAFT',
            ]);
        });
    }
}