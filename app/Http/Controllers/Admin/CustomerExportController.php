<?php

namespace App\Http\Controllers\Admin;

use App\Exports\CustomersExport;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\LaravelPdf\Facades\Pdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CustomerExportController extends Controller
{
    public function download(Request $request): BinaryFileResponse
    {
        $export = new CustomersExport(
            search: $request->string('q')->value(),
            filterStatus: $request->string('status')->value(),
        );

        if ($request->input('format') === 'csv') {
            return Excel::download($export, 'customers.csv', ExcelFormat::CSV);
        }

        return Excel::download($export, 'customers.xlsx');
    }

    public function pdf(Request $request): Response
    {
        $customers = User::query()
            ->withBanned()
            ->whereDoesntHave('roles')
            ->withCount('orders')
            ->withSum('orders', 'total_cents')
            ->when($request->string('q')->value(), function ($query, $search) {
                $term = '%'.$search.'%';
                $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->when($request->string('status')->value() === 'active', fn ($q) => $q->whereNull('banned_at'))
            ->when($request->string('status')->value() === 'banned', fn ($q) => $q->whereNotNull('banned_at'))
            ->orderBy('name')
            ->get();

        return Pdf::view('exports.customers-pdf', ['customers' => $customers])
            ->format('A4')
            ->download('customers.pdf')
            ->toResponse($request);
    }
}
