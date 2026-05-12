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
     * Batch evaluate multiple answers.
     * Returns array of ['marks', 'feedback'] keyed by index.
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
