<?php

namespace App\Exports\ExamBulk;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class PictureSubQuestionsSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(private bool $withExamples) {}

    public function headings(): array
    {
        return [
            'Question number',
            'Label (a, b, c…)',
            'Sub-question text',
            'Correct answer',
            'Marks',
        ];
    }

    public function collection(): Collection
    {
        if (! $this->withExamples) {
            return collect();
        }

        // Matches question 6 in the Questions sheet example
        return collect([
            [6, 'a', 'What animal is shown in the picture?', 'cat', 1],
            [6, 'b', 'What sound does this animal make?',    'meow', 1],
            [6, 'c', 'How many legs does this animal have?', '4',   1],
        ]);
    }

    public function title(): string
    {
        return 'Picture sub-questions';
    }
}
