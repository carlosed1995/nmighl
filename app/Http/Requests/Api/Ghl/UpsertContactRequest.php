<?php

namespace App\Http\Requests\Api\Ghl;

use Illuminate\Foundation\Http\FormRequest;

class UpsertContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ghl_contact_id'   => 'required|string|max:128',
            'ghl_location_id'  => 'required|string|max:128',
            'name'             => 'nullable|string|max:255',
            'email'            => 'nullable|email|max:255',
            'phone'            => 'nullable|string|max:64',
            'tags'             => 'nullable|array',
            'tags.*'           => 'string|max:64',
            'last_activity_at' => 'nullable|date',
            'raw'              => 'nullable|array',
        ];
    }
}
