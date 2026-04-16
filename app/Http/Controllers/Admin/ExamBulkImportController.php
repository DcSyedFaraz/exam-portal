<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ExamBulkTemplateExport;
use App\Http\Controllers\Controller;
use App\Jobs\ImportExamWorkbookJob;
use App\Models\ExamImportBatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExamBulkImportController extends Controller
{
    public function index(): View
    {
        $batches = ExamImportBatch::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->limit(15)
            ->get();

        return view('admin.exams.bulk-import', compact('batches'));
    }

    public function template(Request $request): BinaryFileResponse
    {
        $withExamples = $request->boolean('with_examples');

        return Excel::download(
            new ExamBulkTemplateExport($withExamples),
            $withExamples ? 'exam_bulk_template_with_examples.xlsx' : 'exam_bulk_template_empty.xlsx'
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:'.implode(',', config('exam_import.allowed_mimes')),
                'max:'.config('exam_import.max_file_kb'),
            ],
        ]);

        $disk = config('exam_import.disk');
        $dir = config('exam_import.upload_directory');
        $stored = $request->file('file')->store($dir, $disk);

        $batch = ExamImportBatch::create([
            'user_id' => auth()->id(),
            'original_filename' => $request->file('file')->getClientOriginalName(),
            'stored_path' => $stored,
            'status' => 'pending',
        ]);

        $fullPath = Storage::disk($disk)->path($stored);
        $estimatedRows = $this->estimateRowCounts($fullPath);

        $queued = $estimatedRows > (int) config('exam_import.queue_threshold_rows');

        if ($queued) {
            ImportExamWorkbookJob::dispatch($batch->id);
            $message = 'Import has been queued. Refresh this page in a few moments to see the result.';
        } else {
            ImportExamWorkbookJob::dispatchSync($batch->id);
            $message = 'Import finished.';
        }

        return redirect()
            ->route('admin.exams.bulk-import.show', $batch)
            ->with('success', $message);
    }

    public function show(ExamImportBatch $batch): View
    {
        abort_unless($batch->user_id === auth()->id(), 403);

        return view('admin.exams.bulk-import-show', compact('batch'));
    }

    public function errors(ExamImportBatch $batch): BinaryFileResponse
    {
        abort_unless($batch->user_id === auth()->id(), 403);

        $path = $batch->error_report_path;
        $disk = config('exam_import.disk');

        if (! $path || ! Storage::disk($disk)->exists($path)) {
            abort(404);
        }

        return response()->download(
            Storage::disk($disk)->path($path),
            'exam_import_errors_'.$batch->id.'.xlsx'
        );
    }

    private function estimateRowCounts(string $absolutePath): int
    {
        try {
            $reader = IOFactory::createReaderForFile($absolutePath);
            if (! method_exists($reader, 'listWorksheetInfo')) {
                return PHP_INT_MAX;
            }

            $info = $reader->listWorksheetInfo($absolutePath);
            $sum = 0;
            foreach ($info as $sheet) {
                $name = $sheet['worksheetName'] ?? '';
                if (in_array($name, ['Exam details', 'Questions', 'Matching pairs'], true)) {
                    $sum += max(0, (int) ($sheet['totalRows'] ?? 0) - 1);
                }
            }

            return $sum;
        } catch (\Throwable) {
            return PHP_INT_MAX;
        }
    }
}
