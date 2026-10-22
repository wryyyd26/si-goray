<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookingRequest extends FormRequest
{
    /**
     * Pengguna harus sudah login.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Validasi pembuatan booking.
     */
    public function rules(): array
    {
        return [
            'identity_id' => [
                'required',
                'uuid',
                Rule::exists('identities', 'id')
                    ->where('user_id', $this->user()->id),
            ],

            'jenis_booking' => [
                'required',
                Rule::in(['REGULER', 'EVENT']),
            ],

            'regular_slot_id' => [
                'nullable',
                'uuid',
                'required_if:jenis_booking,REGULER',
                'prohibited_if:jenis_booking,EVENT',
                'exists:regular_slots,id',
            ],

            'event_schedule_id' => [
                'nullable',
                'uuid',
                'required_if:jenis_booking,EVENT',
                'prohibited_if:jenis_booking,REGULER',
                'exists:event_schedules,id',
            ],
        ];
    }
}
