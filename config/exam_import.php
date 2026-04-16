<?php

return [
    'max_file_kb' => (int) env('EXAM_IMPORT_MAX_FILE_KB', 5120),

    'max_questions_per_file' => (int) env('EXAM_IMPORT_MAX_QUESTIONS', 5000),

    'max_match_pair_rows' => (int) env('EXAM_IMPORT_MAX_MATCH_ROWS', 10000),

    'chunk_size' => (int) env('EXAM_IMPORT_CHUNK_SIZE', 500),

    /** When estimated data rows (exam details + questions + matching pairs) exceed this, processing runs in a queued job. */
    'queue_threshold_rows' => (int) env('EXAM_IMPORT_QUEUE_THRESHOLD', 100),

    'job_timeout' => (int) env('EXAM_IMPORT_JOB_TIMEOUT', 600),

    'allowed_mimes' => ['xlsx', 'xls'],

    'disk' => env('EXAM_IMPORT_DISK', 'local'),

    'upload_directory' => 'exam-imports',

    'error_report_directory' => 'exam-import-errors',
];
