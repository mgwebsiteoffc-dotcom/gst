<?php

namespace App\Http\Requests;

use App\Models\RecurringInvoice;
use App\Support\Gstin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecurringInvoiceRequest extends FormRequest
{
    public function authorize(): bool { return (bool) $this->user(); }

    public function rules(): array
    {
        return [
            'doc_type' => ['required', Rule::in(array_keys(RecurringInvoice::DOC_TYPES))],
            'tax_mode' => ['nullable', Rule::in(['gst', 'non_gst'])],
            'party_id' => ['required', 'exists:parties,id'],

            'frequency' => ['required', Rule::in(array_keys(RecurringInvoice::FREQUENCIES))],
            'interval_count' => ['required', 'integer', 'min:1', 'max:52'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after:starts_on'],
            'max_occurrences' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'due_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'auto_finalize' => ['boolean'],

            'place_of_supply_code' => ['nullable', Rule::in(array_keys(Gstin::STATES))],
            'reverse_charge' => ['boolean'],
            'is_export' => ['boolean'],
            'with_payment_of_tax' => ['boolean'],
            'shipping_charge' => ['numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'terms' => ['nullable', 'string', 'max:2000'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['nullable', 'exists:items,id'],
            'lines.*.name' => ['required', 'string', 'max:255'],
            'lines.*.description' => ['nullable', 'string', 'max:1000'],
            'lines.*.hsn_sac' => ['nullable', 'string', 'max:8'],
            'lines.*.uqc' => ['nullable', 'string', 'max:8'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.rate' => ['required', 'numeric', 'min:0'],
            'lines.*.gst_rate' => [Rule::requiredIf(fn () => $this->input('tax_mode', 'gst') === 'gst'),
                'nullable', 'numeric', Rule::in([0, 0.25, 3, 5, 12, 18, 28, 40])],
            'lines.*.cess_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
