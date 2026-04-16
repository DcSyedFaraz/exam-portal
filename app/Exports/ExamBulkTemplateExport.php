<?php

namespace App\Exports;

use App\Exports\ExamBulk\ExamDetailsSheet;
use App\Exports\ExamBulk\MatchingPairsFriendlySheet;
use App\Exports\ExamBulk\QuestionsFriendlySheet;
use App\Exports\ExamBulk\ReadMeSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ExamBulkTemplateExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(private bool $withExamples) {}

    public function sheets(): array
    {
        return [
            new ReadMeSheet($this->withExamples),
            new ExamDetailsSheet($this->withExamples),
            new QuestionsFriendlySheet($this->withExamples),
            new MatchingPairsFriendlySheet($this->withExamples),
        ];
    }
}
