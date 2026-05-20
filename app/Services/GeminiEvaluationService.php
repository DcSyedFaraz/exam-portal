<?php

namespace App\Services;

use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\Log;

class GeminiEvaluationService
{
    /**
     * Evaluate a student answer against the correct answer.
     * Returns ['marks' => float, 'feedback' => string]
     */
    public function evaluate(
        string $studentAnswer,
        string $correctAnswer,
        int $maxMarks,
        string $questionText = ''
    ): array {
        $studentAnswer = strip_tags(trim(mb_substr($studentAnswer, 0, 500)));
        $correctAnswer = strip_tags(trim(mb_substr($correctAnswer, 0, 500)));
        $questionText  = strip_tags(trim(mb_substr($questionText, 0, 300)));

        if (empty($studentAnswer)) {
            return ['marks' => 0, 'feedback' => 'No answer provided.'];
        }

        $prompt = <<<PROMPT
You are grading a student exam answer. Be fair and consistent.

Question: {$questionText}
Correct answer: {$correctAnswer}
Student answer: {$studentAnswer}
Max marks: {$maxMarks}

Rules:
- Award full marks if meaning matches, even if wording differs
- Award partial marks if partially correct
- Award 0 if completely wrong or irrelevant
- Respond ONLY in this exact JSON format, nothing else:
{"marks": <number>, "feedback": "<one sentence>"}
PROMPT;

        try {
            $response = Gemini::generativeModel(config('gemini.model', 'gemini-2.0-flash'))->generateContent($prompt);
            $text = trim($response->text());

            $text = preg_replace('/```json|```/', '', $text);
            $text = trim($text);

            $result = json_decode($text, true);

            if (! isset($result['marks']) || ! isset($result['feedback'])) {
                throw new \Exception('Invalid Gemini response structure');
            }

            $marks = max(0, min((float) $result['marks'], $maxMarks));

            return [
                'marks'    => $marks,
                'feedback' => strip_tags(mb_substr($result['feedback'], 0, 300)),
            ];
        } catch (\Exception $e) {
            Log::error('Gemini evaluation failed: ' . $e->getMessage());

            return [
                'marks'    => 0,
                'feedback' => 'AI evaluation failed. Pending manual review.',
            ];
        }
    }

    /**
     * Evaluate ALL AI questions in a SINGLE Gemini API request.
     *
     * @param  array  $items  Each item: ['question' => string, 'correct_answer' => string,
     *                                    'student_answer' => string, 'max_marks' => int]
     * @return array  Same-indexed array of ['marks' => float, 'feedback' => string]
     */
    public function evaluateAll(array $items): array
    {
        if (empty($items)) {
            return [];
        }

        // Build an empty result set as fallback
        $fallback = [];
        foreach ($items as $idx => $item) {
            $fallback[$idx] = ['marks' => 0, 'feedback' => 'AI evaluation failed. Pending manual review.'];
        }

        // Sanitize inputs and build numbered question list for the prompt
        $lines = [];
        foreach ($items as $idx => $item) {
            $question      = strip_tags(trim(mb_substr($item['question']        ?? '', 0, 300)));
            $correctAnswer = strip_tags(trim(mb_substr($item['correct_answer']  ?? '', 0, 500)));
            $studentAnswer = strip_tags(trim(mb_substr($item['student_answer']  ?? '', 0, 500)));
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
            $response = Gemini::generativeModel(config('gemini.model', 'gemini-2.0-flash'))->generateContent($prompt);
            $text = trim($response->text());

            // Strip markdown code fences if present
            $text = preg_replace('/```json\s*|```\s*/i', '', $text);
            $text = trim($text);

            $results = json_decode($text, true);

            if (! is_array($results)) {
                throw new \Exception('Gemini batch response is not a JSON array');
            }

            $output = $fallback; // start with fallback, overwrite on success

            foreach ($results as $row) {
                if (! isset($row['index'], $row['marks'], $row['feedback'])) {
                    continue;
                }
                $idx = $row['index']; // keep as-is (may be int or string like "pic_42")
                if (! array_key_exists($idx, $items)) {
                    continue; // skip unknown indices
                }
                $maxMarks = (int) ($items[$idx]['max_marks'] ?? 1);
                $marks    = max(0, min((float) $row['marks'], $maxMarks));

                $output[$idx] = [
                    'marks'    => $marks,
                    'feedback' => strip_tags(mb_substr((string) $row['feedback'], 0, 300)),
                ];
            }

            return $output;

        } catch (\Exception $e) {
            Log::error('Gemini batch evaluation failed: ' . $e->getMessage());
            return $fallback;
        }
    }

    /**
     * Batch evaluate multiple answers (legacy: loops individually).
     * Returns array of ['marks', 'feedback'] keyed by index.
     *
     * @deprecated  Use evaluateAll() for a single API request.
     */
    public function evaluateBatch(array $items, int $maxMarksEach): array
    {
        $results = [];
        foreach ($items as $index => $item) {
            if ($index > 0) {
                usleep(200000); // 200ms delay to avoid rate limiting
            }
            $results[$index] = $this->evaluate(
                $item['student_answer'],
                $item['correct_answer'],
                $maxMarksEach,
                $item['question'] ?? ''
            );
        }

        return $results;
    }
}
