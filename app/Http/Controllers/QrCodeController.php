<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeController extends Controller
{
    public function show(Asset $asset)
    {
        // URL untuk halaman profil publik/internal aset
        $url = route('assets.show', $asset->id);
        
        // Generate SVG dengan margin 1 agar bisa mudah diprint
        $svg = QrCode::size(250)->margin(1)->generate($url);
        
        return response($svg)->header('Content-Type', 'image/svg+xml');
    }
}
