<?php

namespace App\Services\ExamImport;

use App\Exports\ExamImportErrorsExport;
use App\Models\Exam;
use App\Models\ExamImportBatch;
use App\Models\Option;
use App\Models\Question;
use App\Models\StudentProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Single-exam workbook (template version 2): sheets "Read me", "Exam details", "Questions", optional "Matching pairs".
 */
final class ExamWorkbookImporterV1
{
    /** @var array<int, array{sheet: string, row: int|string, reference: string, message: string}> */
    private array $errors = [];

    public function process(int $batchId): void
    {
        $batch = ExamImportBatch::query()->findOrFail($batchId);
        $disk = config('exam_import.disk');
        $path = $batch->stored_path;
        $userId = (int) $batch->user_id;

        if (! Storage::disk($disk)->exists($path)) {
            $this->failBatch($batch, 'Uploaded file is missing or expired.');

            return;
        }

        $fullPath = Storage::disk($disk)->path($path);

        $batch->update(['status' => 'processing']);

        $this->errors = [];

        try {
            $reader = IOFactory::createReaderForFile($fullPath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($fullPath);
        } catch (\Throwable $e) {
            $this->failBatch($batch, 'Could not read workbook: '.$e->getMessage());

            return;
        }

        $readMe          = $spreadsheet->getSheetByName('Read me');
        $examSheet       = $spreadsheet->getSheetByName('Exam details');
        $questionsSheet  = $spreadsheet->getSheetByName('Questions');
        $matchingSheet   = $spreadsheet->getSheetByName('Matching pairs');
        $wordBankSheet   = $spreadsheet->getSheetByName('Word Bank items');
        $pictureSheet    = $spreadsheet->getSheetByName('Picture sub-questions');

        if (! $readMe || ! $examSheet || ! $questionsSheet) {
            $this->addError('Workbook', '-', '-', 'Missing a required sheet. You need: Read me, Exam details, and Questions. The other sheets are optional.');
            $this->finishBatch($batch, []);

            return;
        }

        $version = trim((string) $readMe->getCell('B1')->getValue());
        if ($version === '1') {
            $this->addError('Read me', 1, '-', 'This file uses an older layout. Please download a new template from Admin › Exams › Import from Excel.');
            $this->finishBatch($batch, []);

            return;
        }
        if ($version !== '2') {
            $this->addError('Read me', 1, '-', 'This file version is not supported. On the Read me tab, cell B1 next to Template version must be 2. Download the latest template.');
            $this->finishBatch($batch, []);

            return;
        }

        $examRows     = $this->readDataRows($examSheet);
        $questionRows = $this->readDataRows($questionsSheet);
        $matchRows    = $matchingSheet  ? $this->readDataRows($matchingSheet)  : [];
        $wbRows       = $wordBankSheet  ? $this->readDataRows($wordBankSheet)  : [];
        $picRows      = $pictureSheet   ? $this->readDataRows($pictureSheet)   : [];

        $examRowsWithTitle = array_values(array_filter($examRows, function (array $r) {
            $t = trim((string) ($r['data']['exam_title'] ?? ''));

            return $t !== '';
        }));

        if (count($examRowsWithTitle) === 0) {
            $this->addError('Exam details', '-', 'Exam', 'Please fill in the first row under the headings with your exam title and other details.');
        } elseif (count($examRowsWithTitle) > 1) {
            $this->addError('Exam details', '-', 'Exam', 'Only one exam is allowed per file. Remove extra rows under Exam details.');
        }

        if (count($questionRows) > config('exam_import.max_questions_per_file')) {
            $this->addError('Questions', '-', '-', 'Too many question rows (max '.config('exam_import.max_questions_per_file').').');
        }
        if (count($matchRows) > config('exam_import.max_match_pair_rows')) {
            $this->addError('Matching pairs', '-', '-', 'Too many matching rows (max '.config('exam_import.max_match_pair_rows').').');
        }

        if ($questionRows === []) {
            $this->addError('Questions', '-', 'Questions', 'Add at least one question on the Questions sheet.');
        }

        if ($this->errors !== []) {
            $this->finishBatch($batch, []);

            return;
        }

        $examRow = $examRowsWithTitle[0];
        $examData = $examRow['data'];
        $examRowNum = $examRow['row_number'];

        $examErrors = $this->validateExamDetails($examData, $examRowNum);
        foreach ($examErrors as $er) {
            $this->addError($er[0], $er[1], $er[2], $er[3]);
        }

        if ($this->errors !== []) {
            $this->finishBatch($batch, []);

            return;
        }

        // ── Matching pairs map ────────────────────────────────────────────
        $pairsMap = [];
        foreach ($matchRows as ['row_number' => $rowNum, 'data' => $row]) {
            $qn    = (int) ($row['question_number'] ?? 0);
            $left  = trim((string) ($row['left_item'] ?? ''));
            $right = trim((string) ($row['right_item'] ?? ''));
            if ($qn < 1) {
                $this->addError('Matching pairs', $rowNum, 'Matching', 'Question number must be a whole number 1, 2, 3…');
                continue;
            }
            if ($left === '' || $right === '') {
                $this->addError('Matching pairs', $rowNum, 'Q'.$qn, 'Left item and Right item cannot be empty.');
                continue;
            }
            $pairsMap[$qn][] = [$left, $right, $rowNum];
        }

        // ── Word Bank items map ───────────────────────────────────────────
        // Columns: question_number, sentence_(with_____for_the_blank), correct_word
        $wbMap = [];
        foreach ($wbRows as ['row_number' => $rowNum, 'data' => $row]) {
            $qn       = (int) ($row['question_number'] ?? 0);
            // normalised header can differ; try both key forms
            $sentence = trim((string) ($row['sentence_(with_____for_the_blank)'] ?? $row['sentence'] ?? ''));
            $word     = trim((string) ($row['correct_word'] ?? ''));
            if ($qn < 1) {
                $this->addError('Word Bank items', $rowNum, 'Word Bank', 'Question number must be a whole number 1, 2, 3…');
                continue;
            }
            if ($sentence === '' || $word === '') {
                $this->addError('Word Bank items', $rowNum, 'Q'.$qn, 'Sentence and Correct word cannot be empty.');
                continue;
            }
            $wbMap[$qn][] = [$sentence, $word];
        }

        // ── Picture sub-questions map ─────────────────────────────────────
        // Columns: question_number, label_(a,_b,_c…), sub-question_text, correct_answer, marks
        $picMap = [];
        foreach ($picRows as ['row_number' => $rowNum, 'data' => $row]) {
            $qn     = (int) ($row['question_number'] ?? 0);
            $label  = strtolower(trim((string) ($row['label_(a,_b,_c…)'] ?? $row['label'] ?? '')));
            $subQ   = trim((string) ($row['sub-question_text'] ?? $row['sub_question_text'] ?? ''));
            $ans    = trim((string) ($row['correct_answer'] ?? ''));
            $marks  = max(1, (int) ($row['marks'] ?? 1));
            if ($qn < 1) {
                $this->addError('Picture sub-questions', $rowNum, 'Picture', 'Question number must be a whole number 1, 2, 3…');
                continue;
            }
            if ($subQ === '') {
                $this->addError('Picture sub-questions', $rowNum, 'Q'.$qn, 'Sub-question text cannot be empty.');
                continue;
            }
            $picMap[$qn][] = ['label' => $label ?: chr(97 + count($picMap[$qn] ?? [])), 'question' => $subQ, 'answer' => $ans, 'marks' => $marks];
        }

        if ($this->errors !== []) {
            $this->finishBatch($batch, []);

            return;
        }

        $orderSeen = [];
        $qList = [];
        foreach ($questionRows as $q) {
            $rowNum = $q['row_number'];
            $data = $q['data'];
            $ord = (int) ($data['question_number'] ?? 0);
            if ($ord < 1) {
                $this->addError('Questions', $rowNum, 'Questions', 'Question number must be a whole number starting at 1.');

                continue;
            }
            if (isset($orderSeen[$ord])) {
                $this->addError('Questions', $rowNum, 'Q'.$ord, 'This question number is used twice. Each row needs a different number.');

                continue;
            }
            $orderSeen[$ord] = true;
            $kind = $this->parseQuestionKind((string) ($data['type_of_question'] ?? ''));
            if ($kind === null) {
                $this->addError('Questions', $rowNum, 'Q'.$ord, 'Type of question must be something like "Multiple choice", "True or false", or "Matching".');

                continue;
            }
            $qList[] = ['row_number' => $rowNum, 'data' => $data, 'kind' => $kind, 'order' => $ord];
        }

        if ($this->errors !== []) {
            $this->finishBatch($batch, []);

            return;
        }

        foreach ($qList as $q) {
            $this->errors = array_merge($this->errors, $this->validateQuestionRowFriendly($q['row_number'], $q['data'], $q['kind'], $q['order'], $pairsMap, $wbMap, $picMap));
        }

        $totalMarks = (int) ($examData['total_marks'] ?? 0);
        $sum = 0;
        foreach ($qList as $q) {
            $sum += (int) ($q['data']['marks_for_this_question'] ?? 0);
        }
        if ($qList !== [] && $sum !== $totalMarks) {
            $this->addError('Questions', '-', 'Marks', 'The marks for each question add up to '.$sum.', but Total marks on Exam details is '.$totalMarks.'. These two numbers must match.');
        }

        if ($this->errors !== []) {
            $this->finishBatch($batch, []);

            return;
        }

        $createdExamIds = [];

        try {
            DB::transaction(function () use ($examData, $qList, $pairsMap, $wbMap, $picMap, $userId, &$createdExamIds) {
                $exam = Exam::create([
                    'title' => trim((string) $examData['exam_title']),
                    'description' => $this->nullableString($examData['description'] ?? null),
                    'duration_minutes' => (int) ($examData['time_allowed_in_minutes'] ?? 0),
                    'is_published' => $this->parsePublishYesNo($examData['publish_this_exam'] ?? ''),
                    'passing_marks' => (int) ($examData['passing_marks'] ?? 0),
                    'total_marks' => (int) ($examData['total_marks'] ?? 0),
                    'created_by' => $userId,
                    'class_level' => $this->parseClassLevel($examData['class_level'] ?? null),
                ]);

                $sorted = collect($qList)->sortBy(fn ($q) => $q['order'])->values()->all();

                foreach ($sorted as $q) {
                    $row   = $q['data'];
                    $type  = $q['kind'];
                    $order = $q['order'];

                    $legacyTypes = ['mcq', 'true_false', 'match'];

                    if (in_array($type, $legacyTypes, true)) {
                        $optionsPayload = $this->buildOptionsPayloadFriendly($type, $row, $order, $pairsMap);

                        QuestionPayloadFactory::assertExactlyOneCorrect($type, $optionsPayload);

                        $question = Question::create([
                            'exam_id'       => $exam->id,
                            'question_text' => trim((string) ($row['your_question'] ?? '')),
                            'question_type' => $type,
                            'marks'         => (int) ($row['marks_for_this_question'] ?? 1),
                            'order'         => $order,
                        ]);

                        foreach ($optionsPayload as $opt) {
                            Option::create([
                                'question_id' => $question->id,
                                'option_text' => $opt['text'],
                                'is_correct'  => (bool) filter_var($opt['is_correct'], FILTER_VALIDATE_BOOL),
                                'match_pair'  => $opt['match_pair'] ?? null,
                            ]);
                        }
                    } else {
                        $wordBankItems = null;
                        if ($type === 'word_bank' && ! empty(trim((string) ($row['word_bank_items'] ?? '')))) {
                            $wordBankItems = array_map('trim', explode(',', (string) $row['word_bank_items']));
                        }

                        $attrs = [
                            'exam_id'             => $exam->id,
                            'question_text'       => strip_tags(trim((string) ($row['your_question'] ?? ''))),
                            'question_type'       => $type,
                            'marks'               => (int) ($row['marks_for_this_question'] ?? 1),
                            'order'               => $order,
                            'correct_answer_text' => in_array($type, ['fill_blank', 'ai_evaluated'], true)
                                                        ? strip_tags(trim((string) ($row['correct_answer'] ?? '')))
                                                        : null,
                            'word_bank_items'     => $wordBankItems,
                            'ai_max_marks'        => $type === 'ai_evaluated'
                                                        ? (int) ($row['marks_for_this_question'] ?? 1)
                                                        : null,
                        ];
                        // Only set fill_blank_grading for fill_blank — let DB default handle other types
                        if ($type === 'fill_blank') {
                            $attrs['fill_blank_grading'] = 'exact';
                        }
                        $question = Question::create($attrs);

                        // Picture: sub-items from "Picture sub-questions" sheet
                        if ($type === 'picture') {
                            foreach ($picMap[$order] ?? [] as $i => $sub) {
                                $question->subItems()->create([
                                    'label'             => $sub['label'],
                                    'sub_question_text' => strip_tags($sub['question']),
                                    'correct_answer'    => strip_tags($sub['answer']),
                                    'marks'             => $sub['marks'],
                                    'order'             => $i,
                                ]);
                            }
                        }

                        // Word Bank: sentence→word pairs from "Word Bank items" sheet
                        if ($type === 'word_bank') {
                            foreach ($wbMap[$order] ?? [] as [$statement, $correctWord]) {
                                $question->options()->create([
                                    'option_text' => $statement,
                                    'match_pair'  => $correctWord,
                                    'is_correct'  => true,
                                ]);
                            }
                        }
                    }
                }

                $createdExamIds[] = $exam->id;
            });
        } catch (ValidationException $e) {
            foreach ($e->errors() as $msgs) {
                foreach ($msgs as $m) {
                    $this->addError('Questions', '-', 'Questions', $m);
                }
            }
        } catch (\Throwable $e) {
            $this->addError('Exam details', $examRowNum, 'Exam', 'Could not save: '.$e->getMessage());
        }

        $this->finishBatch($batch, $createdExamIds);
    }

    /**
     * @return array<int, array{0: string, 1: int|string, 2: string, 3: string}>
     */
    private function validateExamDetails(array $row, int $rowNum): array
    {
        $errs = [];
        $title = trim((string) ($row['exam_title'] ?? ''));
        if ($title === '') {
            $errs[] = ['Exam details', $rowNum, 'Exam', 'Exam title cannot be empty.'];
        }
        if (strlen($title) > 255) {
            $errs[] = ['Exam details', $rowNum, 'Exam', 'Exam title is too long (max 255 characters).'];
        }

        $dur = (int) ($row['time_allowed_in_minutes'] ?? 0);
        if ($dur < 1 || $dur > 300) {
            $errs[] = ['Exam details', $rowNum, 'Exam', 'Time allowed must be between 1 and 300 minutes.'];
        }

        $pass = (int) ($row['passing_marks'] ?? 0);
        $total = (int) ($row['total_marks'] ?? 0);
        if ($pass < 1) {
            $errs[] = ['Exam details', $rowNum, 'Exam', 'Passing marks must be at least 1.'];
        }
        if ($total < 1) {
            $errs[] = ['Exam details', $rowNum, 'Exam', 'Total marks must be at least 1.'];
        }
        if ($pass > $total) {
            $errs[] = ['Exam details', $rowNum, 'Exam', 'Passing marks cannot be higher than total marks.'];
        }

        $cl = $this->parseClassLevel($row['class_level'] ?? null);
        if ($cl !== null && ! in_array($cl, StudentProfile::CLASS_LEVELS, true)) {
            $errs[] = ['Exam details', $rowNum, 'Class', 'Class level was not recognised. Leave it blank or copy a value exactly from the school list.'];
        }

        return $errs;
    }

    private function validateQuestionRowFriendly(int $rowNum, array $row, string $kind, int $order, array $pairsMap, array $wbMap = [], array $picMap = []): array
    {
        $errs = [];
        $ref = 'Q'.$order;

        $text = trim((string) ($row['your_question'] ?? ''));
        if ($text === '') {
            $errs[] = ['Questions', $rowNum, $ref, 'Please write the question text in the "Your question" column.'];
        }

        $marks = (int) ($row['marks_for_this_question'] ?? 0);
        if ($marks < 1) {
            $errs[] = ['Questions', $rowNum, $ref, 'Marks for this question must be at least 1.'];
        }

        if ($kind === 'mcq') {
            try {
                $texts = $this->collectAnswerTexts($row);
                $this->parseRelativeCorrectIndex((string) ($row['correct_answer'] ?? ''), count($texts));
            } catch (\InvalidArgumentException $e) {
                $errs[] = ['Questions', $rowNum, $ref, $e->getMessage()];
            }
        }

        if ($kind === 'true_false') {
            $ca = trim((string) ($row['correct_answer'] ?? ''));
            if ($ca === '') {
                $errs[] = ['Questions', $rowNum, $ref, 'For True or false questions, write True or False in the "Correct answer" column.'];
            } elseif ($this->parseTrueFalseAnswer($ca) === null) {
                $errs[] = ['Questions', $rowNum, $ref, 'Correct answer should be True or False (or Yes/No).'];
            }
        }

        if ($kind === 'match') {
            if (count($pairsMap[$order] ?? []) < 2) {
                $errs[] = ['Questions', $rowNum, $ref, 'Matching questions need at least 2 rows on the "Matching pairs" sheet with this question number.'];
            }
        }

        if ($kind === 'fill_blank') {
            $ca = trim((string) ($row['correct_answer'] ?? ''));
            if ($ca === '') {
                $errs[] = ['Questions', $rowNum, $ref, 'Fill in blank questions need the correct answer(s) in the "Correct answer" column. Use | to separate multiple blanks e.g. east|west'];
            }
        }

        if ($kind === 'ai_evaluated') {
            if (trim((string) ($row['correct_answer'] ?? '')) === '') {
                $errs[] = ['Questions', $rowNum, $ref, 'Open ended questions need a model answer in the "Correct answer" column.'];
            }
        }

        if ($kind === 'word_bank') {
            if (empty(trim((string) ($row['word_bank_items'] ?? '')))) {
                $errs[] = ['Questions', $rowNum, $ref, 'Word Bank questions need comma-separated words in the "word_bank_items" column.'];
            }
            if (empty($wbMap[$order] ?? [])) {
                $errs[] = ['Questions', $rowNum, $ref, 'Word Bank questions need at least one row on the "Word Bank items" sheet with this question number.'];
            }
        }

        if ($kind === 'picture') {
            if (empty($picMap[$order] ?? [])) {
                $errs[] = ['Questions', $rowNum, $ref, 'Picture questions need at least one row on the "Picture sub-questions" sheet with this question number.'];
            }
        }

        return $errs;
    }

    private function parseQuestionKind(string $raw): ?string
    {
        $r = strtolower(trim($raw));
        if ($r === '') {
            return null;
        }

        if (preg_match('/\b(true|yes)\b.*\b(false|no)\b|\b(false|no)\b.*\b(true|yes)\b|true\s*\/\s*false|true\s+or\s+false|\btf\b|\bt\/f\b/i', $r)) {
            return 'true_false';
        }

        if (preg_match('/\b(match|matching)\b/i', $r)) {
            return 'match';
        }

        if (preg_match('/\b(multiple|mcq|choice)\b/i', $r)) {
            return 'mcq';
        }

        if (preg_match('/\bpicture\b/i', $r)) {
            return 'picture';
        }

        if (preg_match('/\bfill.*(blank|in)\b|\bblank\b/i', $r)) {
            return 'fill_blank';
        }

        if (preg_match('/\bword.?bank\b|\bchoose.?from\b/i', $r)) {
            return 'word_bank';
        }

        if (preg_match('/\bopen.?ended\b|\bai.?eval\b|\bessay\b/i', $r)) {
            return 'ai_evaluated';
        }

        return null;
    }

    /**
     * @param  array<int, list<array{0: string, 1: string, 2: int}>>  $pairsMap
     * @return array<int, array{text: string, is_correct: string, match_pair?: ?string}>
     */
    private function buildOptionsPayloadFriendly(string $type, array $row, int $order, array $pairsMap): array
    {
        if ($type === 'mcq') {
            $texts = $this->collectAnswerTexts($row);
            $idx = $this->parseRelativeCorrectIndex((string) ($row['correct_answer'] ?? ''), count($texts));

            return QuestionPayloadFactory::mcqFromTextsAndCorrectIndex($texts, $idx);
        }

        if ($type === 'true_false') {
            $tf = $this->parseTrueFalseAnswer((string) ($row['correct_answer'] ?? ''));
            if ($tf === null) {
                throw new \InvalidArgumentException('Correct answer must be True or False.');
            }

            return QuestionPayloadFactory::trueFalseFromCorrect($tf);
        }

        $pairs = [];
        foreach ($pairsMap[$order] ?? [] as [$l, $r]) {
            $pairs[] = [$l, $r];
        }

        return QuestionPayloadFactory::matchFromPairs($pairs);
    }

    /** @return array<int, string> */
    private function collectAnswerTexts(array $row): array
    {
        $texts = [];
        for ($i = 1; $i <= 6; $i++) {
            $k = 'answer_'.$i;
            $t = trim((string) ($row[$k] ?? ''));
            if ($t !== '') {
                $texts[] = $t;
            }
        }
        if (count($texts) < 2) {
            throw new \InvalidArgumentException('For multiple choice, fill at least two answer boxes (Answer 1, Answer 2, …) in order.');
        }

        return $texts;
    }

    private function parseTrueFalseAnswer(string $raw): ?bool
    {
        $s = strtolower(trim($raw));
        if (in_array($s, ['1', 'true', 'yes', 'y', 't'], true)) {
            return true;
        }
        if (in_array($s, ['0', 'false', 'no', 'n', 'f'], true)) {
            return false;
        }

        return null;
    }

    private function parseRelativeCorrectIndex(string $raw, int $n): int
    {
        $raw = strtoupper(trim($raw));
        if ($raw === '') {
            throw new \InvalidArgumentException('For multiple choice, fill the "Correct answer" column (for example 2 or B for the second answer).');
        }
        if (ctype_digit($raw)) {
            $i = (int) $raw;
            if ($i < 1 || $i > $n) {
                throw new \InvalidArgumentException('Correct answer must be between 1 and '.$n.' for the answers you filled in.');
            }

            return $i - 1;
        }
        if (strlen($raw) === 1 && $raw >= 'A' && $raw <= 'Z') {
            $i = ord($raw) - ord('A') + 1;
            if ($i < 1 || $i > $n) {
                throw new \InvalidArgumentException('Correct answer letter must match one of your filled answers (A to '.chr(64 + $n).').');
            }

            return $i - 1;
        }

        throw new \InvalidArgumentException('Correct answer should be a number (1, 2, 3…) or a letter (A, B, C…) matching your answer rows.');
    }

    private function parsePublishYesNo(mixed $v): bool
    {
        $s = strtolower(trim((string) $v));

        return in_array($s, ['1', 'true', 'yes', 'y', 'publish', 'published'], true);
    }

    private function nullableString(mixed $v): ?string
    {
        $s = trim((string) ($v ?? ''));

        return $s === '' ? null : $s;
    }

    private function parseClassLevel(mixed $v): ?string
    {
        $s = trim((string) ($v ?? ''));

        return $s === '' ? null : $s;
    }

    private function normalizeHeader(string $h): string
    {
        $h = strtolower(trim(str_replace([' ', '-'], '_', (string) $h)));

        return $h;
    }

    /**
     * @return array<int, array{row_number: int, data: array<string, mixed>}>
     */
    private function readDataRows(Worksheet $worksheet): array
    {
        $rows = $worksheet->toArray(null, true, true, false);
        if ($rows === [] || $rows[0] === null) {
            return [];
        }

        $headers = array_shift($rows);
        $keys = [];
        foreach ($headers as $h) {
            $keys[] = $this->normalizeHeader((string) ($h ?? ''));
        }

        $out = [];
        foreach ($rows as $idx => $row) {
            if (! is_array($row)) {
                continue;
            }

            $assoc = [];
            foreach ($keys as $i => $key) {
                if ($key === '') {
                    continue;
                }
                $assoc[$key] = $row[$i] ?? null;
            }

            $allEmpty = true;
            foreach ($assoc as $v) {
                if (trim((string) ($v ?? '')) !== '') {
                    $allEmpty = false;
                    break;
                }
            }
            if ($allEmpty) {
                continue;
            }

            $out[] = [
                'row_number' => $idx + 2,
                'data' => $assoc,
            ];
        }

        return $out;
    }

    private function addError(string $sheet, int|string $row, string $reference, string $message): void
    {
        $this->errors[] = [
            'sheet' => $sheet,
            'row' => $row,
            'reference' => $reference,
            'message' => $message,
        ];
    }

    private function failBatch(ExamImportBatch $batch, string $message): void
    {
        $batch->update([
            'status' => 'failed',
            'summary_json' => ['fatal' => $message],
        ]);
    }

    /**
     * @param  array<int, int>  $createdExamIds
     */
    private function finishBatch(ExamImportBatch $batch, array $createdExamIds): void
    {
        $disk = config('exam_import.disk');
        $errorPath = null;

        if ($this->errors !== []) {
            $rel = config('exam_import.error_report_directory').'/'.$batch->id.'_errors.xlsx';
            Excel::store(new ExamImportErrorsExport($this->errors), $rel, $disk);
            $errorPath = $rel;
        }

        $status = 'completed';
        if ($this->errors !== [] && $createdExamIds === []) {
            $status = 'failed';
        } elseif ($this->errors !== []) {
            $status = 'partial';
        }

        $batch->update([
            'status' => $status,
            'error_report_path' => $errorPath,
            'summary_json' => [
                'created_exam_ids' => $createdExamIds,
                'errors_count' => count($this->errors),
                'exams_created' => count($createdExamIds),
            ],
        ]);
    }
}
