<?php

namespace App\Contracts;

interface AiEvaluationService
{
    /**
     * Evaluate a single student answer.
     *
     * @return array{marks: float, feedback: string}
     */
    public function evaluate(
        string $studentAnswer,
        string $correctAnswer,
        int    $maxMarks,
        string $questionText = ''
    ): array;

    /**
     * Evaluate multiple answers in a single API call.
     *
     * @param  array  $items  Each: ['question','correct_answer','student_answer','max_marks']
     * @return array  Same-keyed array of ['marks','feedback']
     */
    public function evaluateAll(array $items): array;
}
