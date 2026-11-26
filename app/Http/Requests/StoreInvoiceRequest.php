<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    /**
     * No auth required – always authorize.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer'      => ['required', 'string', 'max:255'],
            'items'         => ['required', 'array', 'min:1'],
            'items.*.name'  => ['required', 'string', 'max:255'],
            'items.*.qty'   => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'gt:0'],
        ];
    }

    /**
     * Clear, human-readable validation error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer.required'      => 'Customer name is required and cannot be empty or null.',
            'customer.string'        => 'Customer name must be a valid string.',
            'items.required'         => 'At least one invoice item is required.',
            'items.array'            => 'Items must be provided as a list.',
            'items.min'              => 'The invoice must contain at least one item.',
            'items.*.name.required'  => 'Each item must have a name.',
            'items.*.name.string'    => 'Each item name must be a valid string.',
            'items.*.qty.required'   => 'Item quantity is required and cannot be null.',
            'items.*.qty.integer'    => 'Item quantity must be a whole number.',
            'items.*.qty.min'        => 'Item quantity must be at least 1 (zero or negative values are not allowed).',
            'items.*.price.required' => 'Item price is required and cannot be null.',
            'items.*.price.numeric'  => 'Item price must be a valid number.',
            'items.*.price.gt'       => 'Item price must be greater than 0 (zero or negative values are not allowed).',
        ];
    }

    /**
     * Only pass through the validated fields – strip any client-sent
     * totals (subtotal, tax, total) so the server always computes them.
     *
     * @return array{customer: string, items: array}
     */
    public function validatedInvoiceData(): array
    {
        $v = $this->validated();

        return [
            'customer' => $v['customer'],
            'items'    => $v['items'],
        ];
    }
}
