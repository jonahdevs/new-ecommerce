<?php

namespace App\Http\Controllers\Admin;

use App\Exports\OrdersExport;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
            filterDate: $request->string('date')->value(),
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
            ->when($request->string('date')->value() === 'today', fn ($q) => $q->whereDate('created_at', today()))
            ->when($request->string('date')->value() === 'week', fn ($q) => $q->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]))
            ->when($request->string('date')->value() === 'month', fn ($q) => $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year))
            ->latest()
            ->get();

        return Pdf::view('exports.orders-pdf', ['orders' => $orders])
            ->format('A4', landscape: true)
            ->download('orders.pdf')
            ->toResponse($request);
    }
}
