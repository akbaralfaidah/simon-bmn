<?php

namespace App\Http\Requests;

use App\Models\Asset;
use App\Services\AccessScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! in_array($this->route('module'), ['transfers', 'incidents'])) {
            return false;
        }
        $asset = Asset::find($this->input('asset_id'));
        if (! $asset) {
            return false;
        }
        $scope = app(AccessScope::class);

        return $this->route('module') === 'incidents'
            ? $this->user()->can('view', $asset)
            : $scope->coordinate($this->user(), $asset) || $scope->inspect($this->user(), $asset);
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255', 'asset_id' => 'required|uuid|exists:assets,id', 'notes' => 'required|string|max:5000',
            'target_room_id' => [Rule::requiredIf($this->route('module') === 'transfers'), 'nullable', Rule::in(app(AccessScope::class)->roomIds($this->user(), true))],
            'category' => [Rule::requiredIf($this->route('module') === 'incidents'), 'nullable', Rule::in(['damage', 'missing', 'other'])],
            'register_type' => [Rule::requiredIf($this->route('module') === 'registers'), 'nullable', Rule::in(['ASP', 'PSP'])],
            'reference_number' => [Rule::requiredIf($this->route('module') === 'registers'), 'nullable', 'string', 'max:255'],
            'reference_date' => [Rule::requiredIf($this->route('module') === 'registers'), 'nullable', 'date', 'before_or_equal:today'],
            'images' => 'sometimes|array|max:4', 'images.*' => 'required|image|mimes:jpeg,png,webp|max:10240|dimensions:max_width=12000,max_height=12000',
        ];
    }
}
