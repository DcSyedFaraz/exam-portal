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
            // MCQ options
            'Answer 1',
            'Answer 2',
            'Answer 3',
            'Answer 4',
            'Answer 5',
            'Answer 6',
            // Shared correct answer (MCQ index, True/False answer, Fill-in-blank answers, AI model answer)
            'Correct answer',
            // Word Bank only
            'word_bank_items',
        ];
    }

    public function collection(): Collection
    {
        if (! $this->withExamples) {
            return collect();
        }

        return collect([
            // ── Multiple choice ──────────────────────────────────────────────
            [
                1,
                'Multiple choice',
                'Which planet is known as the Red Planet?',
                2,
                'Venus', 'Mars', 'Jupiter', 'Saturn', '', '',
                '2',    // answer 2 = Mars
                '',
            ],
            // ── True or false ────────────────────────────────────────────────
            [
                2,
                'True or false',
                'The Pacific Ocean is the largest ocean on Earth.',
                1,
                '', '', '', '', '', '',
                'True',
                '',
            ],
            // ── Matching ─────────────────────────────────────────────────────
            [
                3,
                'Matching',
                'Match each capital city to its country.',
                3,
                '', '', '', '', '', '',
                '',     // pairs go on the "Matching pairs" sheet
                '',
            ],
            // ── Fill in blank (2 blanks) ─────────────────────────────────────
            [
                4,
                'Fill in blank',
                'The sun rises in the ______ and sets in the ______.',
                2,
                '', '', '', '', '', '',
                'east|west',   // pipe-separated, one per blank
                '',
            ],
            // ── Word Bank ────────────────────────────────────────────────────
            [
                5,
                'Word Bank',
                'Use the words in the box to fill in the blanks.',
                2,
                '', '', '', '', '', '',
                '',     // pairs go on the "Word Bank items" sheet
                'Ocean, Forest, Desert, Farm',
            ],
            // ── Picture ──────────────────────────────────────────────────────
            [
                6,
                'Picture',
                'Look at the picture and answer the following questions.',
                3,
                '', '', '', '', '', '',
                '',     // sub-questions go on the "Picture sub-questions" sheet
                '',
            ],
            // ── Open ended ───────────────────────────────────────────────────
            [
                7,
                'Open ended',
                'Tell me about your favourite animal. What does it look like and what does it eat?',
                2,
                '', '', '', '', '', '',
                'My favourite animal is a dog. It has four legs and fur. It eats meat and dog food.',
                '',
            ],
        ]);
    }

    public function title(): string
    {
        return 'Questions';
    }
}
