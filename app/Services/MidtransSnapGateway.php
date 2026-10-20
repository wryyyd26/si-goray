<?php

namespace App\Services;

use Midtrans\Snap;

class MidtransSnapGateway
{
    public function createToken(array $params): string
    {
        return Snap::getSnapToken($params);
    }
}
