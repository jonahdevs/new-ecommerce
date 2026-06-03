<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SapSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'products' => ['required', 'array', 'min:1'],
            'products.*.sku' => ['required', 'string'],
            'products.*.price' => ['required', 'numeric', 'min:0'],
            'products.*.stock_quantity' => ['required', 'integer', 'min:0'],
        ];
    }
}
