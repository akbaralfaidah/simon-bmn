<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Inertia\Inertia;

class QrCodeController extends Controller
{
    public function show(Asset $asset)
    {
        $this->authorize('view', $asset);

        return Inertia::render('Assets/QrCode', [
            'asset' => $asset->only(['id', 'name', 'nup', 'item_code', 'brand_type', 'serial_number']),
        ]);
    }
}
