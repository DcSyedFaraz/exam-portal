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
            ['Step 1', 'Fill in the "Exam details" sheet with your exam name, time limit, and scores.'],
            ['Step 2', 'Go to "Questions" and add one row per question. Use the Type of question values listed below.'],
            ['Step 3', 'Matching only — add pairs on the "Matching pairs" sheet with the same question number.'],
            ['Step 4', 'Word Bank only — add sentences on the "Word Bank items" sheet with the same question number.'],
            ['Step 5', 'Picture only — add sub-questions on the "Picture sub-questions" sheet with the same question number.'],
            [],
            ['── QUESTION TYPES ──', ''],
            [],
            ['Multiple choice',  'Fill Answer 1 to Answer 6. In "Correct answer" write the number (1, 2…) or letter (A, B…) of the correct option.'],
            ['True or false',    'Leave the Answer columns empty. In "Correct answer" write True or False.'],
            ['Matching',         'Leave Answer columns empty. Add each left→right pair on the "Matching pairs" sheet using this question number.'],
            ['Fill in blank',    'Write the sentence in "Your question" using ______ (6 underscores) for each blank. In "Correct answer" write answers separated by | e.g. Dar es Salaam|Indian'],
            ['Word Bank',        'Write an instruction in "Your question". List the word choices in "word_bank_items" separated by commas. Add each sentence+answer on "Word Bank items" sheet.'],
            ['Picture',          'Write a caption in "Your question". Add sub-questions on the "Picture sub-questions" sheet. NOTE: upload the image manually via the admin editor after import.'],
            ['Open ended',       'Write the question in "Your question". Write a model/correct answer in "Correct answer" — AI uses this to grade the student response.'],
            [],
            ['── MARKS ──', ''],
            ['',                 'Marks in "Marks for this question" must add up to "Total marks" on Exam details.'],
            ['',                 'For Picture questions set "Marks for this question" to the total of all sub-question marks combined.'],
        ];

        if ($this->withExamples) {
            $rows[] = [];
            $rows[] = ['Example file', 'The other sheets contain a small sample exam. Change or delete the example rows as needed.'];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Read me';
    }
}
