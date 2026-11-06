<?php

namespace App\Services;

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;

/**
 * Mengubah payload bertanda tangan dari QrTicketService menjadi gambar saja.
 * Tidak membuat signature/tiket baru dan tidak menggunakan layanan pihak ketiga.
 */
class QrSvgRenderer
{
    public function render(string $payloadSigned): string
    {
        $qrCode = new QrCode(
            data: $payloadSigned,
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 380,
            margin: 16,
        );

        return (new SvgWriter())->write($qrCode)->getString();
    }
}
