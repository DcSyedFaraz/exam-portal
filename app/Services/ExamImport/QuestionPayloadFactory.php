<?php

namespace App\Services\ExamImport;

use Illuminate\Validation\ValidationException;

final class QuestionPayloadFactory
{
    /**
     * @param  array<int, array{text: string, is_correct: bool|string, match_pair?: ?string}>  $options
     */
    public static function countCorrect(array $options): int
    {
        return collect($options)->filter(function ($opt) {
            if (! is_array($opt)) {
                return false;
            }
            $v = $opt['is_correct'] ?? false;

            return filter_var($v, FILTER_VALIDATE_BOOL);
        })->count();
    }

    /**
     * @param  array<int, array{text: string, is_correct: bool|string, match_pair?: ?string}>  $options
     */
    public static function assertExactlyOneCorrect(string $questionType, array $options): void
    {
        if (! in_array($questionType, ['mcq', 'true_false'], true)) {
            return;
        }

        if (self::countCorrect($options) !== 1) {
            throw ValidationException::withMessages([
                'options' => ['Please select exactly one correct answer.'],
            ]);
        }
    }

    /**
     * @return array<int, array{text: string, is_correct: string, match_pair?: null}>
     */
    public static function mcqFromTextsAndCorrectIndex(array $optionTexts, int $correctZeroBasedIndex): array
    {
        $out = [];
        foreach ($optionTexts as $i => $text) {
            $out[] = [
                'text' => $text,
                'is_correct' => $i === $correctZeroBasedIndex ? '1' : '0',
                'match_pair' => null,
            ];
        }

        return $out;
    }

    /**
     * @return array<int, array{text: string, is_correct: string, match_pair?: null}>
     */
    public static function trueFalseFromCorrect(bool $correctIsTrue): array
    {
        return [
            ['text' => 'True', 'is_correct' => $correctIsTrue ? '1' : '0', 'match_pair' => null],
            ['text' => 'False', 'is_correct' => $correctIsTrue ? '0' : '1', 'match_pair' => null],
        ];
    }

    /**
     * @param  array<int, array{0: string, 1: string}>  $leftRightPairs
     * @return array<int, array{text: string, is_correct: string, match_pair: string}>
     */
    public static function matchFromPairs(array $leftRightPairs): array
    {
        $out = [];
        foreach ($leftRightPairs as [$left, $right]) {
            $out[] = [
                'text' => $left,
                'is_correct' => '1',
                'match_pair' => $right,
            ];
        }

        return $out;
    }
}
