<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExamImportErrorsExport implements FromCollection, WithHeadings
{
    /**
     * @param  array<int, array{sheet: string, row: int|string, reference: string, message: string}>  $errors
     */
    public function __construct(private array $errors) {}

    public function headings(): array
    {
        return ['Sheet', 'Row', 'Where', 'What went wrong'];
    }

    public function collection(): Collection
    {
        return collect($this->errors)->map(fn (array $e) => [
            $e['sheet'],
            $e['row'],
            $e['reference'],
            $e['message'],
        ]);
    }
}
