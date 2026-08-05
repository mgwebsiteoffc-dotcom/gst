<?php

namespace App\Http\Requests;

use App\Models\Document;
use App\Support\Gstin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentRequest extends FormRequest
{
    public function authorize(): bool { return (bool) $this->user(); }

    public function rules(): array
    {
        return [
            'doc_type' => ['required', Rule::in(array_keys(Document::TYPES))],
            'tax_mode' => ['nullable', Rule::in(['gst', 'non_gst'])],
            // Posted by the form's Save-draft / Save-and-finalise buttons. Without
            // this rule validated() strips it and everything stays a draft.
            'status' => ['nullable', Rule::in(['draft', 'sent'])],
            'party_id' => ['nullable', 'exists:parties,id'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'place_of_supply_code' => ['nullable', Rule::in(array_keys(Gstin::STATES))],
            'reverse_charge' => ['boolean'],
            'is_export' => ['boolean'],
            'with_payment_of_tax' => ['boolean'],
            'shipping_charge' => ['numeric', 'min:0'],
            'tds_percent' => ['numeric', 'min:0', 'max:100'],
            'tcs_percent' => ['numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'terms' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['nullable', 'exists:items,id'],
            'lines.*.name' => ['required', 'string', 'max:255'],
            'lines.*.hsn_sac' => ['nullable', 'string', 'max:8'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.rate' => ['required', 'numeric', 'min:0'],
            // GST rate is only mandatory on GST-mode documents.
            'lines.*.gst_rate' => [Rule::requiredIf(fn () => ($this->input('tax_mode', 'gst') === 'gst')),
                'nullable', 'numeric', Rule::in([0, 0.25, 3, 5, 12, 18, 28, 40])],
            'lines.*.cess_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
