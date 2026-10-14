<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookingRequest extends FormRequest
{
    /**
     * Menentukan apakah user boleh melakukan request ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi data booking.
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'uuid',
                'exists:users,id',
            ],

            'identity_id' => [
                'required',
                'uuid',
                'exists:identities,id',
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