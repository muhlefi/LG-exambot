<?php

namespace App\Http\Controllers;

abstract class Controller
{
    public static function generateQrCode(string $url, int $size = 300): string
    {
        return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($url);
    }
}
