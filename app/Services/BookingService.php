<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\EventSchedule;
use App\Models\RegularSlot;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BookingService
{
    /**
     * Membuat booking baru dengan status DRAFT.
     *
     * Proses:
     * 1. Validasi jenis booking.
     * 2. Validasi jadwal sesuai jenis booking.
     * 3. Mengunci slot/jadwal dengan lockForUpdate().
     * 4. Mengecek kapasitas.
     * 5. Membuat booking.
     * 6. Menambah kuota_terpakai.
     *
     * Seluruh proses dijalankan dalam DB transaction.
     */
    public function createBooking(array $data): Booking
    {
        return DB::transaction(function () use ($data) {

            $jenisBooking = strtoupper($data['jenis_booking'] ?? '');

            /*
             * Validasi jenis booking.
             */
            if (!in_array($jenisBooking, ['REGULER', 'EVENT'], true)) {
                throw new InvalidArgumentException(
                    'Jenis booking harus REGULER atau EVENT.'
                );
            }

            /*
             * Variabel jadwal.
             */
            $regularSlot = null;
            $eventSchedule = null;

            /*
             * ==========================================
             * BOOKING REGULER
             * ==========================================
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

                /*
                 * Ambil slot REGULER sekaligus menguncinya.
                 *
                 * Selama transaction berjalan, baris ini
                 * tidak boleh diubah oleh transaksi lain.
                 */
                $regularSlot = RegularSlot::where('id', $data['regular_slot_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$regularSlot) {
                    throw new InvalidArgumentException(
                        'Regular slot tidak ditemukan.'
                    );
                }

                /*
                 * Cek kapasitas REGULER.
                 */
                if ($regularSlot->kuota_terpakai >= $regularSlot->kapasitas) {
                    throw new InvalidArgumentException(
                        'Kuota booking REGULER sudah penuh.'
                    );
                }
            }

            /*
             * ==========================================
             * BOOKING EVENT
             * ==========================================
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

                /*
                 * Ambil jadwal EVENT sekaligus menguncinya.
                 */
                $eventSchedule = EventSchedule::where(
                    'id',
                    $data['event_schedule_id']
                )
                    ->lockForUpdate()
                    ->first();

                if (!$eventSchedule) {
                    throw new InvalidArgumentException(
                        'Event schedule tidak ditemukan.'
                    );
                }

                /*
                 * Cek kapasitas EVENT.
                 */
                if ($eventSchedule->kuota_terpakai >= $eventSchedule->kapasitas) {
                    throw new InvalidArgumentException(
                        'Kuota booking EVENT sudah penuh.'
                    );
                }
            }

            /*
             * ==========================================
             * BUAT BOOKING
             * ==========================================
             */
            $booking = Booking::create([
                'user_id' => $data['user_id'],
                'identity_id' => $data['identity_id'] ?? null,

                'jenis_booking' => $jenisBooking,

                'regular_slot_id' => $regularSlot?->id,
                'event_schedule_id' => $eventSchedule?->id,

                'tariff_id' => $data['tariff_id'],

                'retribusi_diperlukan' =>
                    $data['retribusi_diperlukan'] ?? false,

                'status_booking' => 'DRAFT',
            ]);

            /*
             * ==========================================
             * TAMBAH KUOTA TERPAKAI
             * ==========================================
             */
            if ($regularSlot) {

                $regularSlot->increment('kuota_terpakai');

            } elseif ($eventSchedule) {

                $eventSchedule->increment('kuota_terpakai');
            }

            /*
             * Jika seluruh proses berhasil,
             * transaction akan COMMIT.
             *
             * Jika terjadi error sebelum selesai,
             * seluruh perubahan akan ROLLBACK.
             */
            return $booking;
        });
    }
}