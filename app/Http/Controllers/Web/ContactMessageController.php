<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SaranMasukan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactMessageController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:254'],
            'whatsapp' => ['nullable', 'string', 'max:25'],
            'category' => ['required', Rule::in(['feedback', 'reservasi'])],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        SaranMasukan::create($data);

        return redirect()->route('contact')->with('success', 'Masukan berhasil dikirim. Terima kasih!');
    }
}