<?php

namespace App\Http\Requests\Api\Ghl;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|string|in:draft,sent,paid,pending,overdue,void',
        ];
    }
}
