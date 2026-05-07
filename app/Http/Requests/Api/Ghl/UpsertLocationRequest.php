<?php

namespace App\Http\Requests\Api\Ghl;

use Illuminate\Foundation\Http\FormRequest;

class UpsertLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ghl_id'     => 'required|string|max:128',
            'name'       => 'required|string|max:255',
            'company_id' => 'nullable|string|max:128',
            'timezone'   => 'nullable|string|max:64',
            'raw'        => 'nullable|array',
        ];
    }
}
