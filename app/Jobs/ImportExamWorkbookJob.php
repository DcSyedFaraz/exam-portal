<?php

namespace App\Jobs;

use App\Models\ExamImportBatch;
use App\Services\ExamImport\ExamWorkbookImporterV1;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportExamWorkbookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout;

    public int $tries = 1;

    public function __construct(public int $batchId)
    {
        $this->timeout = (int) config('exam_import.job_timeout');
    }

    public function handle(ExamWorkbookImporterV1 $importer): void
    {
        $importer->process($this->batchId);
    }

    public function failed(?\Throwable $e): void
    {
        ExamImportBatch::query()->whereKey($this->batchId)->update([
            'status' => 'failed',
            'summary_json' => ['fatal' => $e?->getMessage() ?? 'Import job failed.'],
        ]);
    }
}
