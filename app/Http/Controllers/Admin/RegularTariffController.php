<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TariffReference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegularTariffController extends Controller
{
    public function update(Request $request)
    {
        $data = $request->validate([
            'non_warga_price' => [
                'required',
                'integer',
                'min:1',
                'max:999999999',
            ],
        ]);

        DB::transaction(function () use ($data) {
            // Hanya tarif umum reguler, bukan tarif khusus event.
            TariffReference::query()
                ->where('jenis_pengunjung', 'UMUM')
                ->whereNull('event_id')
                ->where('aktif', true)
                ->update(['aktif' => false]);

            // Simpan tarif baru sebagai record baru.
            // ID dan nominal tarif lama tetap tersimpan.
            TariffReference::create([
                'event_id' => null,
                'jenis_pengunjung' => 'UMUM',
                'nominal' => $data['non_warga_price'],
                'berlaku_mulai' => today()->toDateString(),
                'aktif' => true,
            ]);
        });

        return back()->with(
            'success',
            'Tarif reguler berhasil diperbarui.'
        );
    }
}
