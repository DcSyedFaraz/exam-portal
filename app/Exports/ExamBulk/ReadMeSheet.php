<?php

namespace App\Exports\ExamBulk;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class ReadMeSheet implements FromArray, WithTitle
{
    public function __construct(private bool $withExamples) {}

    public function array(): array
    {
        $rows = [
            ['Template version', '2'],
            ['Important', 'Please leave the number 2 in cell B1. The system uses it to read this file correctly.'],
            ['What this is for', 'This workbook creates one exam with all of its questions.'],
            [],
            ['Step 1', 'Fill in the blue sheet "Exam details" with your exam name, time limit, and scores.'],
            ['Step 2', 'Go to "Questions" and add one row per question.'],
            ['Step 3', 'For "Matching" questions only, add the pairs on the "Matching pairs" sheet using the same question number.'],
            [],
            ['Question types', 'In the "Type of question" column you can write: Multiple choice, True or false, or Matching.'],
            ['Multiple choice', 'Fill Answer 1 to Answer 4 or more. In "Correct answer" type the number of the correct line (1, 2, 3…) or a letter (A, B, C…).'],
            ['True or false', 'Leave the answer boxes empty. In "Correct answer" write True or False.'],
            ['Matching', 'Leave the answer boxes empty. Add each pair on "Matching pairs" with the same question number. You need at least two pairs.'],
            [],
            ['Marks', 'The numbers in "Marks for this question" must add up to "Total marks" on the Exam details sheet.'],
            ['Class level', 'You can leave Class level blank, or copy a value from the list on the Exam details sheet example.'],
        ];

        if ($this->withExamples) {
            $rows[] = [];
            $rows[] = ['Example file', 'The other sheets already contain a small sample exam you can change or delete.'];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Read me';
    }
}
