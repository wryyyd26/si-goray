<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PengunjungPesananController extends Controller
{
    public function index(Request $request): View|Response
    {
        if (!Auth::guard('web')->check()) {
            return redirect()->route('pengunjung.login', ['next' => '/pesanan-saya']);
        }

        $pesanan = Booking::query()
            ->where('user_id', (string) Auth::guard('web')->id())
            ->with(['paymentTransactions', 'qrTickets'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('pengunjung.pesanan.index', compact('pesanan'));
    }
}
