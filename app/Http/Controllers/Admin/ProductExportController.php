<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ProductsExport;
use App\Exports\ProductsMissingImagesExport;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\LaravelPdf\Facades\Pdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductExportController extends Controller
{
    public function download(Request $request): BinaryFileResponse
    {
        $export = $this->buildExport($request);

        if ($request->input('format') === 'csv') {
            return Excel::download($export, 'products.csv', ExcelFormat::CSV);
        }

        return Excel::download($export, 'products.xlsx');
    }

    public function pdf(Request $request): Response
    {
        $products = Product::query()
            ->with(['brand', 'primaryCategory'])
            ->when($request->string('q')->value(), function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')
                        ->orWhere('sku', 'like', '%'.$search.'%');
                });
            })
            ->when($request->string('status')->value(), fn ($q, $status) => $q->where('status', $status))
            ->when($request->string('visibility')->value(), fn ($q, $vis) => $q->where('visibility', $vis))
            ->when($request->string('stock')->value(), fn ($q, $stock) => $q->where('stock_status', $stock))
            ->orderBy('name')
            ->get();

        return Pdf::view('exports.products-pdf', ['products' => $products])
            ->format('A4')
            ->download('products.pdf')
            ->toResponse($request);
    }

    public function template(): StreamedResponse
    {
        $headings = [
            'ID', 'Name', 'SKU', 'Brand', 'Primary Category',
            'Type', 'Status', 'Price (KES)', 'Sale Price (KES)', 'Cost Price (KES)',
            'Stock Status', 'Stock Quantity', 'Visibility',
            'Weight', 'Is Taxable', 'Requires Shipping',
            'Short Description', 'Meta Title', 'Meta Description',
        ];

        $sample = [
            '', 'Example Product', 'SKU-001', 'Acme Brand', 'Electronics',
            'simple', 'draft', '1500', '', '1000',
            'in_stock', '50', 'visible',
            '0.5', 'yes', 'yes',
            'A short description of the product.', 'Example Product | My Store', 'Buy this product online.',
        ];

        return response()->stream(function () use ($headings, $sample) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headings);
            fputcsv($handle, $sample);
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="products-import-template.csv"',
        ]);
    }

    public function missingImages(): BinaryFileResponse
    {
        return Excel::download(new ProductsMissingImagesExport, 'products-missing-images.xlsx');
    }

    private function buildExport(Request $request): ProductsExport
    {
        return new ProductsExport(
            search: $request->string('q')->value(),
            filterStatus: $request->string('status')->value(),
            filterVisibility: $request->string('visibility')->value(),
            filterStock: $request->string('stock')->value(),
        );
    }
}
