<?php

namespace App\Http\Controllers\Admin;

use App\Exports\QuotesExport;
use App\Http\Controllers\Controller;
use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\LaravelPdf\Facades\Pdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class QuoteExportController extends Controller
{
    public function download(Request $request): BinaryFileResponse
    {
        $export = new QuotesExport(
            search: $request->string('q')->value(),
            filterStatus: $request->string('status')->value(),
        );

        if ($request->input('format') === 'csv') {
            return Excel::download($export, 'quotes.csv', ExcelFormat::CSV);
        }

        return Excel::download($export, 'quotes.xlsx');
    }

    public function pdf(Request $request): Response
    {
        $quotes = Quote::query()
            ->with('user')
            ->withCount('items')
            ->when($request->string('q')->value(), function ($query, $search) {
                $term = '%'.$search.'%';
                $query->where(function ($q) use ($term) {
                    $q->where('quote_number', 'like', $term)
                        ->orWhere('contact_name', 'like', $term)
                        ->orWhere('contact_email', 'like', $term)
                        ->orWhere('contact_company', 'like', $term)
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term));
                });
            })
            ->when($request->string('status')->value(), fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->get();

        return Pdf::view('exports.quotes-pdf', ['quotes' => $quotes])
            ->format('A4', landscape: true)
            ->download('quotes.pdf')
            ->toResponse($request);
    }
}
