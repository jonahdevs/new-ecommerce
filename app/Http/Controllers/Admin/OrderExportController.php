<?php

namespace App\Http\Controllers\Admin;

use App\Exports\OrdersExport;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\LaravelPdf\Facades\Pdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrderExportController extends Controller
{
    public function download(Request $request): BinaryFileResponse
    {
        $export = new OrdersExport(
            search: $request->string('q')->value(),
            filterStatus: $request->string('status')->value(),
            dateFrom: $request->string('from')->value(),
            dateTo: $request->string('to')->value(),
        );

        if ($request->input('format') === 'csv') {
            return Excel::download($export, 'orders.csv', ExcelFormat::CSV);
        }

        return Excel::download($export, 'orders.xlsx');
    }

    public function pdf(Request $request): Response
    {
        $orders = Order::query()
            ->with(['user', 'latestPayment'])
            ->withCount('items')
            ->when($request->string('q')->value(), function ($query, $search) {
                $term = '%'.$search.'%';
                $query->where(function ($q) use ($term) {
                    $q->where('order_number', 'like', $term)
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term));
                });
            })
            ->when($request->string('status')->value(), fn ($q, $status) => $q->where('status', $status))
            ->when($request->filled('from') && $request->filled('to'), fn ($q) => $q->whereBetween('created_at', [
                Carbon::parse($request->string('from')->value())->startOfDay(),
                Carbon::parse($request->string('to')->value())->endOfDay(),
            ]))
            ->latest()
            ->get();

        return Pdf::view('exports.orders-pdf', ['orders' => $orders])
            ->format('A4', landscape: true)
            ->download('orders.pdf')
            ->toResponse($request);
    }
}
