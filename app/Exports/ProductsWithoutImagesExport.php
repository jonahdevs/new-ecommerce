<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ProductsWithoutImagesExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithColumnWidths,
    WithTitle
{
    protected Collection $products;

    public function __construct(Collection $products)
    {
        $this->products = $products;
    }

    /**
     * Return the collection of products
     */
    public function collection()
    {
        return $this->products;
    }

    /**
     * Define the headings for the Excel file
     */
    public function headings(): array
    {
        return [
            'Product Name',
            'SKU',
            'Brand',
            'Model Number',
            'Category',
            'Price (KES)',
            'Quantity',
            'Image Status',
            'Gallery Status',
            'Short Description',
        ];
    }

    /**
     * Map each product to the Excel row
     */
    public function map($product): array
    {
        // Check image status
        $imageStatus = 'Missing';
        if (isset($product['image']) && !empty(trim($product['image']))) {
            $imageStatus = 'Has Image';
        }

        // Check gallery status
        $galleryStatus = 'No Gallery';
        if (isset($product['gallery']) && is_array($product['gallery']) && count($product['gallery']) > 0) {
            $galleryStatus = count($product['gallery']) . ' images';
        }

        return [
            $product['name'] ?? 'N/A',
            $product['sku'] ?? 'N/A',
            $product['brand'] ?? 'N/A',
            $product['model_number'] ?? 'N/A',
            $product['category'] ?? 'N/A',
            isset($product['price']) ? number_format($product['price'], 2) : 'N/A',
            $product['quantity'] ?? 'N/A',
            $imageStatus,
            $galleryStatus,
            isset($product['short_description']) ? substr($product['short_description'], 0, 100) : 'N/A',
        ];
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the header row
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    /**
     * Define column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 30, // Product Name
            'B' => 20, // SKU
            'C' => 15, // Brand
            'D' => 15, // Model Number
            'E' => 20, // Category
            'F' => 15, // Price
            'G' => 10, // Quantity
            'H' => 15, // Image Status
            'I' => 15, // Gallery Status
            'J' => 50, // Short Description
        ];
    }

    /**
     * Set the sheet title
     */
    public function title(): string
    {
        return 'Products Without Images';
    }
}
