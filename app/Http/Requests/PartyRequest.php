<?php

namespace App\Http\Requests;

use App\Rules\ValidGstin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PartyRequest extends FormRequest
{
    public function authorize(): bool { return (bool) $this->user(); }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['customer', 'vendor', 'both'])],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'gstin' => ['nullable', new ValidGstin],
            'gst_treatment' => ['required', Rule::in(['regular', 'composition', 'unregistered', 'consumer', 'overseas', 'sez'])],
            'billing_address' => ['nullable', 'string', 'max:255'],
            'billing_city' => ['nullable', 'string', 'max:255'],
            'billing_state_code' => ['nullable', 'string', 'size:2'],
            'billing_pincode' => ['nullable', 'digits:6'],
            'credit_days' => ['nullable', 'integer', 'min:0', 'max:365'],
        ];
    }
}
