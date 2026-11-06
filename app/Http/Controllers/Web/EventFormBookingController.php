<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\EventSchedule;
use App\Models\Identity;
use App\Services\BookingService;
use App\Services\IdentityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;

/** Thin adapter: all booking eligibility, tariff, and quota decisions remain in BookingService. */
class EventFormBookingController extends Controller
{
    public function storeWarga(Request $request, IdentityService $identities, BookingService $bookings): RedirectResponse
    {
        return $this->store($request, true, $identities, $bookings);
    }

    public function storeLuar(Request $request, IdentityService $identities, BookingService $bookings): RedirectResponse
    {
        return $this->store($request, false, $identities, $bookings);
    }

    private function store(Request $request, bool $expectsWarga, IdentityService $identities, BookingService $bookings): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'event_id' => ['required', 'uuid', 'exists:events,id'],
            'event_schedule_id' => ['required', 'uuid', 'exists:event_schedules,id'],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'digits:16'],
            'tanggal_lahir' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'nomor_hp' => ['required', 'string', 'max:20'],
            'alamat_ktp' => ['nullable', 'string', 'max:1000'],
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput($request->except(['nik', 'tanggal_lahir']));
        }
        $data = $validator->validated();

        $user = $request->user('web');
        abort_unless($user, 403);

        if (mb_strtolower(trim($data['email'])) !== mb_strtolower(trim((string) $user->no_hp_email))) {
            return back()->withErrors(['email' => 'Email harus sesuai dengan akun yang sedang login.'])
                ->withInput($request->except(['nik', 'tanggal_lahir']));
        }

        $schedule = EventSchedule::query()
            ->whereKey($data['event_schedule_id'])
            ->where('event_id', $data['event_id'])
            ->first();

        if (!$schedule) {
            return back()->withErrors(['event_schedule_id' => 'Jadwal tidak sesuai dengan event yang dipilih.'])
                ->withInput($request->except(['nik', 'tanggal_lahir']));
        }

        $hashKey = config('identity.hash_key');
        if (!is_string($hashKey) || $hashKey === '') {
            report(new RuntimeException('IDENTITY_HASH_KEY belum tersedia.'));
            return back()->with('error', 'Layanan validasi identitas sedang tidak tersedia.');
        }

        try {
            $booking = DB::transaction(function () use ($data, $user, $hashKey, $expectsWarga, $identities, $bookings): Booking {
                $nikHash = hash_hmac('sha256', trim($data['nik']), $hashKey);
                $identity = Identity::query()->where('nik_hash', $nikHash)->first();

                if ($identity) {
                    if ((string) $identity->user_id !== (string) $user->id
                        || (string) $identity->tanggal_lahir_enc !== $data['tanggal_lahir']) {
                        throw new InvalidArgumentException('Identitas tidak cocok dengan akun ini.');
                    }
                } else {
                    $identity = $identities->createIdentity(
                        (string) $user->id,
                        $data['nik'],
                        $data['tanggal_lahir']
                    );
                }

                if ((bool) $identity->status_warga_kota !== $expectsWarga) {
                    throw new InvalidArgumentException('Kategori warga tidak sesuai dengan hasil validasi NIK.');
                }

                return $bookings->createBooking([
                    'user_id' => (string) $user->id,
                    'identity_id' => (string) $identity->id,
                    'jenis_booking' => 'EVENT',
                    'event_schedule_id' => $data['event_schedule_id'],
                ]);
            });
        } catch (RuntimeException $e) {
            report($e);
            return back()->with('error', 'Layanan booking sedang tidak tersedia.');
        } catch (InvalidArgumentException $e) {
            // Avoid flashing raw NIK or birth date to the session on service errors.
            return back()->with('error', $e->getMessage());
        }

        $request->session()->put('booking_id', (string) $booking->id);

        if ($booking->retribusi_diperlukan) {
            return redirect()->route('booking.payment.show', ['booking' => $booking->id]);
        }

        return redirect()->route('events.booking.draft', ['booking' => $booking->id]);
    }

    public function draft(Request $request, Booking $booking): View
    {
        abort_unless((string) $booking->user_id === (string) $request->user('web')?->id, 403);
        $booking->load('eventSchedule.event');

        return view('events.booking-draft', ['booking' => $booking]);
    }
}
