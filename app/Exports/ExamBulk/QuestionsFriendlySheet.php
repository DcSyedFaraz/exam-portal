<?php

namespace App\Exports\ExamBulk;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class QuestionsFriendlySheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(private bool $withExamples) {}

    public function headings(): array
    {
        return [
            'Question number',
            'Type of question',
            'Your question',
            'Marks for this question',
            'Answer 1',
            'Answer 2',
            'Answer 3',
            'Answer 4',
            'Answer 5',
            'Answer 6',
            'Correct answer',
        ];
    }

    public function collection(): Collection
    {
        if (! $this->withExamples) {
            return collect();
        }

        return collect([
            [
                1,
                'Multiple choice',
                'Which planet is known as the Red Planet?',
                4,
                'Venus',
                'Mars',
                'Jupiter',
                'Saturn',
                '',
                '',
                '2',
            ],
            [
                2,
                'True or false',
                'The Pacific Ocean is the largest ocean on Earth.',
                2,
                '',
                '',
                '',
                '',
                '',
                '',
                'True',
            ],
            [
                3,
                'Matching',
                'Match each capital city to its country.',
                4,
                '',
                '',
                '',
                '',
                '',
                '',
                '',
            ],
        ]);
    }

    public function title(): string
    {
        return 'Questions';
    }
}
