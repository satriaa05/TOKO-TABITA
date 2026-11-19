<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Gateway gagal / tidak terkonfigurasi. Tidak pernah dianggap sebagai
 * pembayaran berhasil.
 */
class PaymentGatewayException extends RuntimeException
{
    //
}
