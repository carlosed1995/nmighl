<?php

namespace App\Http\Requests\Api\Ghl;

use Illuminate\Foundation\Http\FormRequest;

class UpsertInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ghl_invoice_id'  => 'required|string|max:128',
            'ghl_contact_id'  => 'required|string|max:128',
            'ghl_location_id' => 'required|string|max:128',
            'invoice_number'  => 'nullable|string|max:64',
            'issued_date'     => 'nullable|date',
            'due_date'        => 'nullable|date',
            'amount'          => 'required|numeric|min:0',
            'status'          => 'required|string|in:draft,sent,paid,pending,overdue,void',
            'raw'             => 'nullable|array',
        ];
    }
}
