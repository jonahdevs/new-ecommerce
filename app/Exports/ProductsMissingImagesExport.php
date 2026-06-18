<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductsMissingImagesExport implements FromQuery, WithColumnWidths, WithHeadings, WithMapping, WithStyles
{
    public function query()
    {
        return Product::query()
            ->with(['brand', 'primaryCategory'])
            ->whereDoesntHave('images')
            ->orderBy('name');
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return [
            'ID', 'Name', 'SKU', 'Model Number', 'Category', 'Brand', 'Status', 'Stock Status', 'Price (KES)',
        ];
    }

    /** @param Product $product */
    public function map($product): array
    {
        return [
            $product->id,
            $product->name,
            $product->sku,
            $product->model_number,
            $product->primaryCategory?->name,
            $product->brand?->name,
            $product->status->value,
            $product->stock_status->value,
            $product->price !== null ? round($product->price / 100, 2) : null,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    /** @return array<string, int> */
    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 40,
            'C' => 18,
            'D' => 18,
            'E' => 25,
            'F' => 20,
            'G' => 12,
            'H' => 14,
            'I' => 14,
        ];
    }
}
