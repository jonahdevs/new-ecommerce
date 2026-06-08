<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomersExport implements FromQuery, WithColumnWidths, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly string $search = '',
        private readonly string $filterStatus = '',
    ) {}

    public function query()
    {
        return User::query()
            ->withBanned()
            ->whereDoesntHave('roles')
            ->withCount('orders')
            ->withSum('orders', 'total_cents')
            ->when($this->search, function ($query) {
                $term = '%'.$this->search.'%';
                $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->when($this->filterStatus === 'active', fn ($q) => $q->whereNull('banned_at'))
            ->when($this->filterStatus === 'banned', fn ($q) => $q->whereNotNull('banned_at'))
            ->orderBy('name');
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return [
            'ID', 'Name', 'Email', 'Status', 'Orders', 'Total Spent (KES)', 'Joined',
        ];
    }

    /** @param User $customer */
    public function map($customer): array
    {
        return [
            $customer->id,
            $customer->name,
            $customer->email,
            $customer->banned_at ? 'Banned' : 'Active',
            $customer->orders_count,
            $customer->orders_sum_total_cents !== null ? round($customer->orders_sum_total_cents / 100, 2) : 0,
            $customer->created_at->format('Y-m-d'),
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
            'B' => 30,
            'C' => 35,
            'D' => 12,
            'E' => 10,
            'F' => 20,
            'G' => 14,
        ];
    }
}
