<?php

namespace App\Services;

use App\Contracts\AiEvaluationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RunwareEvaluationService implements AiEvaluationService
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl;
    protected int    $timeout;

    public function __construct()
    {
        $this->apiKey  = config('ai.runware.api_key', '');
        $this->model   = config('ai.runware.model', 'google:gemini@2.0-flash');
        $this->baseUrl = config('ai.runware.base_url', 'https://api.runware.ai/v1');
        $this->timeout = (int) config('ai.runware.timeout', 30);
    }

    /**
     * Evaluate a single student answer (delegates to batch method).
     */
    public function evaluate(
        string $studentAnswer,
        string $correctAnswer,
        int    $maxMarks,
        string $questionText = ''
    ): array {
        $results = $this->evaluateAll([
            0 => [
                'question'       => $questionText,
                'correct_answer' => $correctAnswer,
                'student_answer' => $studentAnswer,
                'max_marks'      => $maxMarks,
            ],
        ]);

        return $results[0] ?? ['marks' => 0, 'feedback' => 'AI evaluation failed. Pending manual review.'];
    }

    /**
     * Evaluate ALL AI questions in a single Runware API request.
     */
    public function evaluateAll(array $items): array
    {
        if (empty($items)) {
            return [];
        }

        // Build fallback result set
        $fallback = [];
        foreach ($items as $idx => $item) {
            $fallback[$idx] = ['marks' => 0, 'feedback' => 'AI evaluation failed. Pending manual review.'];
        }

        // Sanitize inputs and build numbered question list for the prompt
        $lines = [];
        foreach ($items as $idx => $item) {
            $question      = strip_tags(trim(mb_substr($item['question']       ?? '', 0, 300)));
            $correctAnswer = strip_tags(trim(mb_substr($item['correct_answer'] ?? '', 0, 500)));
            $studentAnswer = strip_tags(trim(mb_substr($item['student_answer'] ?? '', 0, 500)));
            $maxMarks      = (int) ($item['max_marks'] ?? 1);

            $lines[] = json_encode([
                'index'          => $idx,
                'question'       => $question,
                'correct_answer' => $correctAnswer,
                'student_answer' => $studentAnswer,
                'max_marks'      => $maxMarks,
            ], JSON_UNESCAPED_UNICODE);
        }

        $questionsJson = implode("\n", $lines);

        $prompt = <<<PROMPT
You are grading student exam answers. Be fair and consistent.

For each item below, evaluate the student's answer against the correct answer and the question.

Rules:
- Award full marks if the meaning matches, even if wording differs.
- Award partial marks if partially correct.
- Award 0 if completely wrong or irrelevant.
- If student_answer is empty, award 0 marks with feedback "No answer provided."

Items (JSON, one per line):
{$questionsJson}

Respond ONLY with a valid JSON array — one object per item — in this exact format, nothing else:
[
  {"index": <number>, "marks": <number>, "feedback": "<one sentence>"},
  ...
]
PROMPT;

        try {
            $payload = [
                [
                    'taskType'      => 'textInference',
                    'taskUUID'      => (string) Str::uuid(),
                    'model'         => $this->model,
                    'numberResults' => 1,
                    'includeCost'   => true,
                    'messages'      => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'settings'      => [
                        'maxTokens' => 4096,
                    ],
                ],
            ];

            $taskUUID = $payload[0]['taskUUID'];

            // ── Log request ──
            $inputSummary = [];
            foreach ($items as $idx => $item) {
                $inputSummary[] = sprintf(
                    '  [%s] Q: %s | Student: %s | Max: %d',
                    $idx,
                    Str::limit($item['question'] ?? '', 60),
                    Str::limit($item['student_answer'] ?? '(empty)', 40),
                    $item['max_marks'] ?? 1
                );
            }

            Log::debug("Runware AI ── REQUEST ──\n" . implode("\n", [
                "  Provider : runware",
                "  Model    : {$this->model}",
                "  Endpoint : {$this->baseUrl}",
                "  TaskUUID : {$taskUUID}",
                "  Items    : " . count($items),
                "  ── Questions ──",
                ...array_values($inputSummary),
            ]));

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ])
            ->timeout($this->timeout)
            ->post($this->baseUrl, $payload);

            if (! $response->successful()) {
                Log::error("Runware AI ── ERROR ──\n" . implode("\n", [
                    "  TaskUUID : {$taskUUID}",
                    "  Status   : {$response->status()}",
                    "  Body     : {$response->body()}",
                ]));
                throw new \Exception('Runware API returned HTTP ' . $response->status());
            }

            $body = $response->json();

            // Runware returns { data: [ { taskType, taskUUID, text, cost } ] }
            $cost         = $body['data'][0]['cost'] ?? null;
            $finishReason = $body['data'][0]['finishReason'] ?? null;
            $text         = $body['data'][0]['text'] ?? null;

            if (! $text) {
                Log::error("Runware AI ── NO TEXT ──\n" . implode("\n", [
                    "  TaskUUID : {$taskUUID}",
                    "  Body     : " . json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                ]));
                throw new \Exception('No text in Runware response');
            }

            // Strip markdown code fences if present
            $text = preg_replace('/```json\s*|```\s*/i', '', trim($text));

            $results = json_decode($text, true);

            if (! is_array($results)) {
                Log::error("Runware AI ── PARSE FAIL ──\n" . implode("\n", [
                    "  TaskUUID : {$taskUUID}",
                    "  Raw text : {$text}",
                ]));
                throw new \Exception('Runware response is not a JSON array');
            }

            $output = $fallback;

            $gradingSummary = [];
            foreach ($results as $row) {
                if (! isset($row['index'], $row['marks'], $row['feedback'])) {
                    continue;
                }
                $idx = $row['index'];
                if (! array_key_exists($idx, $items)) {
                    continue;
                }
                $maxMarks = (int) ($items[$idx]['max_marks'] ?? 1);
                $marks    = max(0, min((float) $row['marks'], $maxMarks));

                $output[$idx] = [
                    'marks'    => $marks,
                    'feedback' => strip_tags(mb_substr((string) $row['feedback'], 0, 300)),
                ];

                $gradingSummary[] = sprintf(
                    '  [%s] %s/%d — %s',
                    $idx,
                    $marks,
                    $maxMarks,
                    Str::limit($row['feedback'], 80)
                );
            }

            // ── Log response ──
            Log::debug("Runware AI ── RESPONSE ──\n" . implode("\n", [
                "  TaskUUID     : {$taskUUID}",
                "  Status       : {$response->status()}",
                "  Cost         : \${$cost}",
                "  FinishReason : {$finishReason}",
                "  ── Grading Results ──",
                ...array_values($gradingSummary),
            ]));

            return $output;

        } catch (\Exception $e) {
            Log::error('Runware evaluation failed: ' . $e->getMessage());
            return $fallback;
        }
    }
}
