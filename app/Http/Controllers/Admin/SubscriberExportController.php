<?php

namespace App\Http\Controllers\Admin;

use App\Exports\SubscribersExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SubscriberExportController extends Controller
{
    public function __invoke(Request $request): BinaryFileResponse
    {
        $export = new SubscribersExport(
            search: $request->string('q')->value(),
            filterStatus: $request->string('status')->value(),
            filterInterest: $request->string('interest')->value(),
        );

        if ($request->input('format') === 'csv') {
            return Excel::download($export, 'subscribers.csv', ExcelFormat::CSV);
        }

        return Excel::download($export, 'subscribers.xlsx');
    }
}
