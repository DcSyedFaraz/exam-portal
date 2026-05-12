<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\Option;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClassOneQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();

        // ══════════════════════════════════════════════════════════════════
        // EXAM: Class 1 — English & General Knowledge (Mixed New Types)
        // ══════════════════════════════════════════════════════════════════
        $exam = Exam::create([
            'title'            => 'Class 1 — English & General Knowledge',
            'description'      => 'A fun exam for Class 1 students covering fill in the blanks, word bank, picture identification, and open-ended questions.',
            'duration_minutes' => 30,
            'is_published'     => true,
            'passing_marks'    => 10,
            'total_marks'      => 20,
            'class_level'      => 'Class One',
            'created_by'       => $admin->id,
        ]);

        $order = 1;

        // ──────────────────────────────────────────────────────────────────
        // SECTION 1 — Fill in the Blank (5 questions)
        // correct_answer_text stores pipe-joined answers (one per blank)
        // ──────────────────────────────────────────────────────────────────

        $fillBlanks = [
            [
                'text'    => 'The cat sat on the ______.',
                'answers' => ['mat'],
                'grading' => 'exact',
                'marks'   => 1,
            ],
            [
                'text'    => 'The sun rises in the ______.',
                'answers' => ['east'],
                'grading' => 'exact',
                'marks'   => 1,
            ],
            [
                'text'    => 'We drink ______ every day to stay healthy.',
                'answers' => ['water'],
                'grading' => 'exact',
                'marks'   => 1,
            ],
            [
                'text'    => 'A ______ has four legs and says "woof".',
                'answers' => ['dog'],
                'grading' => 'exact',
                'marks'   => 1,
            ],
            [
                'text'    => 'The color of the sky is ______.',
                'answers' => ['blue'],
                'grading' => 'exact',
                'marks'   => 1,
            ],
        ];

        foreach ($fillBlanks as $q) {
            Question::create([
                'exam_id'             => $exam->id,
                'question_text'       => $q['text'],
                'question_type'       => 'fill_blank',
                'marks'               => $q['marks'],
                'order'               => $order++,
                'correct_answer_text' => implode('|', $q['answers']),
                'fill_blank_grading'  => $q['grading'],
            ]);
        }

        // ──────────────────────────────────────────────────────────────────
        // SECTION 2 — Word Bank (3 questions)
        // word_bank_items = array of words shown to student
        // options: option_text = statement, match_pair = correct word
        // ──────────────────────────────────────────────────────────────────

        $wordBanks = [
            [
                'text'  => 'Match each animal with where it lives.',
                'words' => ['Ocean', 'Forest', 'Desert', 'Farm'],
                'items' => [
                    ['statement' => 'A fish lives in the ___.',   'answer' => 'Ocean'],
                    ['statement' => 'A monkey lives in the ___.', 'answer' => 'Forest'],
                    ['statement' => 'A camel lives in the ___.',  'answer' => 'Desert'],
                    ['statement' => 'A cow lives on the ___.',    'answer' => 'Farm'],
                ],
                'marks' => 2,
            ],
            [
                'text'  => 'Fill in the blanks using the correct colour from the word bank.',
                'words' => ['Red', 'Yellow', 'Green', 'White'],
                'items' => [
                    ['statement' => 'Grass is ___.', 'answer' => 'Green'],
                    ['statement' => 'Snow is ___.', 'answer' => 'White'],
                    ['statement' => 'The sun is ___.', 'answer' => 'Yellow'],
                    ['statement' => 'An apple can be ___.', 'answer' => 'Red'],
                ],
                'marks' => 2,
            ],
            [
                'text'  => 'Match each fruit with its correct colour.',
                'words' => ['Orange', 'Yellow', 'Red', 'Purple'],
                'items' => [
                    ['statement' => 'A banana is ___.', 'answer' => 'Yellow'],
                    ['statement' => 'A strawberry is ___.', 'answer' => 'Red'],
                    ['statement' => 'A grape is ___.', 'answer' => 'Purple'],
                    ['statement' => 'An orange is ___.', 'answer' => 'Orange'],
                ],
                'marks' => 2,
            ],
        ];

        foreach ($wordBanks as $q) {
            $question = Question::create([
                'exam_id'        => $exam->id,
                'question_text'  => $q['text'],
                'question_type'  => 'word_bank',
                'marks'          => $q['marks'],
                'order'          => $order++,
                'word_bank_items'=> $q['words'],
            ]);

            foreach ($q['items'] as $item) {
                Option::create([
                    'question_id' => $question->id,
                    'option_text' => $item['statement'],
                    'match_pair'  => $item['answer'],
                    'is_correct'  => true,
                ]);
            }
        }

        // ──────────────────────────────────────────────────────────────────
        // SECTION 3 — Picture Questions (2 questions, exact match grading)
        // ──────────────────────────────────────────────────────────────────

        $pictureQuestions = [
            [
                'caption'    => 'Look at the picture of the cat and answer the questions below.',
                'image_path' => 'question-images/class1_animals.jpg',
                'marks'      => 3,
                'subs'       => [
                    ['label' => 'a', 'question' => 'What animal is shown in the picture?',    'answer' => 'cat',   'marks' => 1],
                    ['label' => 'b', 'question' => 'What sound does this animal make?',       'answer' => 'meow',  'marks' => 1],
                    ['label' => 'c', 'question' => 'How many legs does this animal have?',    'answer' => '4',     'marks' => 1],
                ],
            ],
            [
                'caption'    => 'Look at the picture of the fruit and answer the questions below.',
                'image_path' => 'question-images/class1_fruits.jpg',
                'marks'      => 3,
                'subs'       => [
                    ['label' => 'a', 'question' => 'What fruit is shown in the picture?', 'answer' => 'apple',  'marks' => 1],
                    ['label' => 'b', 'question' => 'What colour is the fruit?',           'answer' => 'red',    'marks' => 1],
                    ['label' => 'c', 'question' => 'Is this fruit a vegetable?',          'answer' => 'no',     'marks' => 1],
                ],
            ],
        ];

        foreach ($pictureQuestions as $q) {
            $question = Question::create([
                'exam_id'            => $exam->id,
                'question_text'      => $q['caption'],
                'question_type'      => 'picture',
                'marks'              => $q['marks'],
                'order'              => $order++,
                'image_path'         => $q['image_path'],
                'fill_blank_grading' => 'exact',
            ]);

            foreach ($q['subs'] as $i => $sub) {
                $question->subItems()->create([
                    'label'             => $sub['label'],
                    'sub_question_text' => $sub['question'],
                    'correct_answer'    => $sub['answer'],
                    'marks'             => $sub['marks'],
                    'order'             => $i,
                ]);
            }
        }

        // ──────────────────────────────────────────────────────────────────
        // SECTION 4 — AI Evaluated / Open-Ended (3 questions)
        // ai_max_marks = full marks; correct_answer_text = model answer for AI
        // ──────────────────────────────────────────────────────────────────

        $aiQuestions = [
            [
                'text'         => 'Tell me about your favourite animal. What does it look like and what does it eat?',
                'model_answer' => 'My favourite animal is a dog. It has four legs, a tail, and fur. It eats meat and dog food.',
                'marks'        => 2,
            ],
            [
                'text'         => 'What do you do every morning before going to school?',
                'model_answer' => 'Every morning I wake up, brush my teeth, wash my face, eat breakfast, and then get dressed before going to school.',
                'marks'        => 2,
            ],
            [
                'text'         => 'Why is it important to drink water every day?',
                'model_answer' => 'It is important to drink water every day because our body needs water to stay healthy. Water keeps us from being thirsty and helps us think clearly.',
                'marks'        => 2,
            ],
        ];

        foreach ($aiQuestions as $q) {
            Question::create([
                'exam_id'             => $exam->id,
                'question_text'       => $q['text'],
                'question_type'       => 'ai_evaluated',
                'marks'               => $q['marks'],
                'order'               => $order++,
                'correct_answer_text' => $q['model_answer'],
                'ai_max_marks'        => $q['marks'],
            ]);
        }

        // Update exam total_marks to match actual questions
        $exam->update(['total_marks' => $exam->questions()->sum('marks')]);

        $this->command->info("✅ Class 1 exam seeded with {$exam->questions()->count()} questions ({$exam->total_marks} total marks).");
    }
}
