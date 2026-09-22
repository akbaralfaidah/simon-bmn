<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'nip' => [
                'nullable',
                'string',
                'max:30',
                function ($attribute, $value, $fail) {
                    if ($value !== null && (str_contains($value, '@') || $value === $this->user()->email)) {
                        $fail('Format NIP tidak boleh berupa alamat email.');
                    }
                },
            ],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }
}
