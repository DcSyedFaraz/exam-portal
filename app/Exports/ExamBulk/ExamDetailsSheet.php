<?php

namespace App\Exports\ExamBulk;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ExamDetailsSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(private bool $withExamples) {}

    public function headings(): array
    {
        return [
            'Exam title',
            'Description',
            'Time allowed in minutes',
            'Passing marks',
            'Total marks',
            'Class level',
            'Publish this exam',
        ];
    }

    public function collection(): Collection
    {
        if (! $this->withExamples) {
            return collect();
        }

        return collect([
            [
                'Sample science quiz',
                'A short sample you can replace with your own text.',
                30,
                6,
                10,
                'Class Six',
                'No',
            ],
        ]);
    }

    public function title(): string
    {
        return 'Exam details';
    }
}
