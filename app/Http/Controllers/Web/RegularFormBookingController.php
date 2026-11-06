<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
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

class RegularFormBookingController extends Controller
{
    public function store(Request $request, IdentityService $identities, BookingService $bookings): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'regular_slot_id' => ['required', 'uuid', 'exists:regular_slots,id'],
            'kategori' => ['required', 'in:warga,luar'],
            'nik' => ['required', 'digits:16'],
            'tanggal_lahir' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'alamat' => ['nullable', 'string', 'max:1000'],
            'asal' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return redirect()->route('activity.reguler')
                ->withErrors($validator)
                ->with('error', 'Data formulir belum valid. Silakan pilih sesi dan isi kembali data diri.');
        }

        $data = $validator->validated();
        $user = $request->user('web');
        abort_unless($user, 403);

        $hashKey = config('identity.hash_key');
        if (!is_string($hashKey) || $hashKey === '') {
            report(new RuntimeException('IDENTITY_HASH_KEY belum tersedia.'));
            return redirect()->route('activity.reguler')
                ->with('error', 'Layanan validasi identitas sedang tidak tersedia.');
        }

        try {
            $booking = DB::transaction(function () use ($data, $user, $hashKey, $identities, $bookings): Booking {
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

                if ((bool) $identity->status_warga_kota !== ($data['kategori'] === 'warga')) {
                    throw new InvalidArgumentException('Kategori warga tidak sesuai dengan hasil validasi NIK.');
                }

                return $bookings->createBooking([
                    'user_id' => (string) $user->id,
                    'identity_id' => (string) $identity->id,
                    'jenis_booking' => 'REGULER',
                    'regular_slot_id' => $data['regular_slot_id'],
                ]);
            });
        } catch (InvalidArgumentException $e) {
            return redirect()->route('activity.reguler')->with('error', $e->getMessage());
        } catch (RuntimeException $e) {
            report($e);
            return redirect()->route('activity.reguler')
                ->with('error', 'Layanan booking sedang tidak tersedia.');
        }

        if ($booking->retribusi_diperlukan) {
            return redirect()->route('booking.payment.show', ['booking' => $booking->id]);
        }

        return redirect()->route('reguler.booking.draft', ['booking' => $booking->id]);
    }

    public function draft(Request $request, Booking $booking): View
    {
        abort_unless((string) $booking->user_id === (string) $request->user('web')?->id, 403);
        abort_unless($booking->jenis_booking === 'REGULER', 404);

        $booking->load('regularSlot');
        return view('regular-booking-draft', compact('booking'));
    }
}
