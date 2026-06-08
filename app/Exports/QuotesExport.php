<?php

namespace App\Exports;

use App\Models\Quote;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class QuotesExport implements FromQuery, WithColumnWidths, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly string $search = '',
        private readonly string $filterStatus = '',
    ) {}

    public function query()
    {
        return Quote::query()
            ->with('user')
            ->withCount('items')
            ->when($this->search, function ($query) {
                $term = '%'.$this->search.'%';
                $query->where(function ($q) use ($term) {
                    $q->where('quote_number', 'like', $term)
                        ->orWhere('contact_name', 'like', $term)
                        ->orWhere('contact_email', 'like', $term)
                        ->orWhere('contact_company', 'like', $term)
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term));
                });
            })
            ->when($this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus))
            ->latest();
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return [
            'Quote #', 'Customer Name', 'Customer Email', 'Company',
            'Items', 'Total (KES)', 'Status', 'Expires At', 'Created At',
        ];
    }

    /** @param Quote $quote */
    public function map($quote): array
    {
        return [
            $quote->quote_number,
            $quote->user?->name ?? $quote->contact_name,
            $quote->user?->email ?? $quote->contact_email,
            $quote->contact_company,
            $quote->items_count,
            $quote->total_cents !== null ? round($quote->total_cents / 100, 2) : null,
            $quote->status->label(),
            $quote->expires_at?->format('Y-m-d'),
            $quote->created_at->format('Y-m-d H:i'),
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
            'A' => 16,
            'B' => 28,
            'C' => 32,
            'D' => 24,
            'E' => 8,
            'F' => 16,
            'G' => 18,
            'H' => 14,
            'I' => 18,
        ];
    }
}
