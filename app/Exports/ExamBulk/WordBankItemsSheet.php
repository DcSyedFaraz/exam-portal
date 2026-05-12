<?php

namespace App\Exports\ExamBulk;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class WordBankItemsSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(private bool $withExamples) {}

    public function headings(): array
    {
        return [
            'Question number',
            'Sentence (with ___ for the blank)',
            'Correct word',
        ];
    }

    public function collection(): Collection
    {
        if (! $this->withExamples) {
            return collect();
        }

        // Matches question 5 in the Questions sheet example
        return collect([
            [5, 'A fish lives in the ___.', 'Ocean'],
            [5, 'A monkey lives in the ___.', 'Forest'],
            [5, 'A camel lives in the ___.', 'Desert'],
            [5, 'A cow lives on the ___.', 'Farm'],
        ]);
    }

    public function title(): string
    {
        return 'Word Bank items';
    }
}
