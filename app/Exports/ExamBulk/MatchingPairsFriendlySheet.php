<?php

namespace App\Exports\ExamBulk;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class MatchingPairsFriendlySheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(private bool $withExamples) {}

    public function headings(): array
    {
        return [
            'Question number',
            'Left item',
            'Right item',
        ];
    }

    public function collection(): Collection
    {
        if (! $this->withExamples) {
            return collect();
        }

        return collect([
            [3, 'Ottawa', 'Canada'],
            [3, 'Paris', 'France'],
            [3, 'Berlin', 'Germany'],
        ]);
    }

    public function title(): string
    {
        return 'Matching pairs';
    }
}
