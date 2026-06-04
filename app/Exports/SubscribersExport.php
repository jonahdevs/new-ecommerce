<?php

namespace App\Exports;

use App\Models\Subscriber;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SubscribersExport implements FromQuery, WithColumnWidths, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly string $search = '',
        private readonly string $filterStatus = '',
        private readonly string $filterInterest = '',
    ) {}

    public function query()
    {
        return Subscriber::query()
            ->when($this->search, fn ($q) => $q->where('email', 'like', '%'.$this->search.'%'))
            ->when($this->filterStatus === 'confirmed', fn ($q) => $q->confirmed())
            ->when($this->filterStatus === 'pending', fn ($q) => $q->pending())
            ->when($this->filterStatus === 'unsubscribed', fn ($q) => $q->unsubscribed())
            ->when($this->filterInterest, fn ($q) => $q->whereJsonContains('interests', $this->filterInterest))
            ->latest();
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['Email', 'Interests', 'Status', 'Source', 'Subscribed At', 'Unsubscribed At', 'Signed Up At'];
    }

    /** @param Subscriber $subscriber */
    public function map($subscriber): array
    {
        $labels = [
            'new-products' => 'New products',
            'seasonal-catalogs' => 'Catalogs',
            'trade-pricing' => 'Trade offers',
            'projects' => 'Projects',
        ];

        $interests = collect($subscriber->interests ?? [])
            ->map(fn ($i) => $labels[$i] ?? $i)
            ->implode(', ');

        $status = $subscriber->isUnsubscribed() ? 'Unsubscribed'
            : ($subscriber->isConfirmed() ? 'Confirmed' : 'Pending');

        return [
            $subscriber->email,
            $interests,
            $status,
            $subscriber->source,
            $subscriber->subscribed_at?->format('Y-m-d H:i:s'),
            $subscriber->unsubscribed_at?->format('Y-m-d H:i:s'),
            $subscriber->created_at->format('Y-m-d H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    /** @return array<string, int> */
    public function columnWidths(): array
    {
        return ['A' => 35, 'B' => 30, 'C' => 14, 'D' => 20, 'E' => 20, 'F' => 20, 'G' => 20];
    }
}
