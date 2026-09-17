<?php

namespace App\Services;

use App\Models\Asset;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssetIdentityService
{
    public function lock(): void
    {
        DB::table('asset_identity_guards')->where('id', 1)->lockForUpdate()->firstOrFail();
    }

    /** @return array{satker_code: ?string, item_code: ?string, nup: ?string} */
    public function snapshot(Asset $asset): array
    {
        return $asset->only(['satker_code', 'item_code', 'nup']);
    }

    public function assertAvailable(array $identity, ?string $exceptAsset = null): void
    {
        if (blank($identity['satker_code'] ?? null) || blank($identity['item_code'] ?? null) || blank($identity['nup'] ?? null)) {
            return;
        }
        $duplicate = Asset::where('satker_code', $identity['satker_code'])->where('item_code', $identity['item_code'])->where('nup', $identity['nup'])
            ->when($exceptAsset, fn ($query) => $query->where('id', '!=', $exceptAsset))->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['nup' => 'Kombinasi satker, kode barang, dan NUP sudah dipakai aset lain. Periksa rekonsiliasi; jangan membuat identitas ganda.']);
        }
    }
}
