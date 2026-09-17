<?php

namespace App\Http\Requests;

use App\Models\Asset;
use App\Services\AccessScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('asset')
            ? $this->user()->can('update', $this->route('asset'))
            : $this->user()->can('create', Asset::class);
    }

    public function rules(): array
    {
        $scope = app(AccessScope::class);

        return [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:asset_categories,id',
            'room_id' => ['required', Rule::in($scope->roomIds($this->user(), true, AccessScope::COORDINATORS))],
            'satker_code' => 'nullable|string|max:100',
            'nup' => 'nullable|string|max:255',
            'item_code' => 'nullable|string|max:255',
            'brand_type' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'specification' => 'nullable|string|max:10000',
            'acquisition_source' => 'nullable|string|max:255',
            'acquisition_date' => 'nullable|date|before_or_equal:today',
            'value' => 'nullable|numeric|min:0|max:9999999999999.99',
            'condition' => 'required|in:Baik,Rusak Ringan,Rusak Berat',
            'is_loanable' => 'sometimes|boolean',
            'version' => 'sometimes|integer|min:1',
            'images' => 'sometimes|array|max:8',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240|dimensions:max_width=12000,max_height=12000',
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            if (array_sum(array_map(fn ($file) => $file->getSize(), $this->file('images', []))) > 40 * 1024 * 1024) {
                $validator->errors()->add('images', 'Total foto maksimal 40 MB.');
            }
        }];
    }
}
