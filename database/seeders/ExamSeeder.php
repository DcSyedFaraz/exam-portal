<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\Option;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExamSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@exam.com')->first();

        // ==========================================
        // Exam 1: MCQ Only
        // ==========================================
        $exam1 = Exam::create([
            'title'            => 'General Knowledge MCQ',
            'description'      => 'Test your general knowledge with multiple choice questions.',
            'duration_minutes' => 30,
            'is_published'     => true,
            'passing_marks'    => 3,
            'total_marks'      => 5,
            'created_by'       => $admin->id,
        ]);

        $mcqQuestions = [
            [
                'text'    => 'What is the capital of France?',
                'options' => [
                    ['text' => 'Berlin',  'correct' => false],
                    ['text' => 'Madrid',  'correct' => false],
                    ['text' => 'Paris',   'correct' => true],
                    ['text' => 'Rome',    'correct' => false],
                ],
            ],
            [
                'text'    => 'Which planet is known as the Red Planet?',
                'options' => [
                    ['text' => 'Earth',   'correct' => false],
                    ['text' => 'Mars',    'correct' => true],
                    ['text' => 'Jupiter', 'correct' => false],
                    ['text' => 'Venus',   'correct' => false],
                ],
            ],
            [
                'text'    => 'What is the largest ocean on Earth?',
                'options' => [
                    ['text' => 'Atlantic Ocean', 'correct' => false],
                    ['text' => 'Indian Ocean',   'correct' => false],
                    ['text' => 'Arctic Ocean',   'correct' => false],
                    ['text' => 'Pacific Ocean',  'correct' => true],
                ],
            ],
            [
                'text'    => 'How many continents are there on Earth?',
                'options' => [
                    ['text' => '5', 'correct' => false],
                    ['text' => '6', 'correct' => false],
                    ['text' => '7', 'correct' => true],
                    ['text' => '8', 'correct' => false],
                ],
            ],
            [
                'text'    => 'What is the chemical symbol for water?',
                'options' => [
                    ['text' => 'O2',  'correct' => false],
                    ['text' => 'H2O', 'correct' => true],
                    ['text' => 'CO2', 'correct' => false],
                    ['text' => 'NaCl','correct' => false],
                ],
            ],
        ];

        foreach ($mcqQuestions as $i => $q) {
            $question = Question::create([
                'exam_id'       => $exam1->id,
                'question_text' => $q['text'],
                'question_type' => 'mcq',
                'marks'         => 1,
                'order'         => $i + 1,
            ]);
            foreach ($q['options'] as $opt) {
                Option::create([
                    'question_id' => $question->id,
                    'option_text' => $opt['text'],
                    'is_correct'  => $opt['correct'],
                ]);
            }
        }

        // ==========================================
        // Exam 2: True/False Only
        // ==========================================
        $exam2 = Exam::create([
            'title'            => 'True or False Challenge',
            'description'      => 'Test your knowledge with true or false questions.',
            'duration_minutes' => 20,
            'is_published'     => true,
            'passing_marks'    => 3,
            'total_marks'      => 5,
            'created_by'       => $admin->id,
        ]);

        $tfQuestions = [
            ['text' => 'The sun is a star.',                       'correct' => 'True'],
            ['text' => 'Humans have four lungs.',                  'correct' => 'False'],
            ['text' => 'Water boils at 100°C at sea level.',       'correct' => 'True'],
            ['text' => 'The Great Wall of China is visible from space.', 'correct' => 'False'],
            ['text' => 'Dolphins are mammals.',                    'correct' => 'True'],
        ];

        foreach ($tfQuestions as $i => $q) {
            $question = Question::create([
                'exam_id'       => $exam2->id,
                'question_text' => $q['text'],
                'question_type' => 'true_false',
                'marks'         => 1,
                'order'         => $i + 1,
            ]);
            Option::create(['question_id' => $question->id, 'option_text' => 'True',  'is_correct' => $q['correct'] === 'True']);
            Option::create(['question_id' => $question->id, 'option_text' => 'False', 'is_correct' => $q['correct'] === 'False']);
        }

        // ==========================================
        // Exam 3: Mixed (MCQ + True/False + Match)
        // ==========================================
        $exam3 = Exam::create([
            'title'            => 'Mixed Assessment',
            'description'      => 'A comprehensive exam with MCQ, True/False, and Match questions.',
            'duration_minutes' => 40,
            'is_published'     => true,
            'passing_marks'    => 4,
            'total_marks'      => 6,
            'created_by'       => $admin->id,
        ]);

        // 2 MCQ
        $mixedMcq = [
            [
                'text'    => 'What is the speed of light?',
                'options' => [
                    ['text' => '300,000 km/s', 'correct' => true],
                    ['text' => '150,000 km/s', 'correct' => false],
                    ['text' => '200,000 km/s', 'correct' => false],
                    ['text' => '450,000 km/s', 'correct' => false],
                ],
            ],
            [
                'text'    => 'Which element has the atomic number 1?',
                'options' => [
                    ['text' => 'Helium',   'correct' => false],
                    ['text' => 'Oxygen',   'correct' => false],
                    ['text' => 'Hydrogen', 'correct' => true],
                    ['text' => 'Carbon',   'correct' => false],
                ],
            ],
        ];

        foreach ($mixedMcq as $i => $q) {
            $question = Question::create([
                'exam_id'       => $exam3->id,
                'question_text' => $q['text'],
                'question_type' => 'mcq',
                'marks'         => 1,
                'order'         => $i + 1,
            ]);
            foreach ($q['options'] as $opt) {
                Option::create(['question_id' => $question->id, 'option_text' => $opt['text'], 'is_correct' => $opt['correct']]);
            }
        }

        // 2 True/False
        $mixedTf = [
            ['text' => 'The Earth revolves around the Sun.', 'correct' => 'True'],
            ['text' => 'Sound travels faster than light.',   'correct' => 'False'],
        ];

        foreach ($mixedTf as $i => $q) {
            $question = Question::create([
                'exam_id'       => $exam3->id,
                'question_text' => $q['text'],
                'question_type' => 'true_false',
                'marks'         => 1,
                'order'         => $i + 3,
            ]);
            Option::create(['question_id' => $question->id, 'option_text' => 'True',  'is_correct' => $q['correct'] === 'True']);
            Option::create(['question_id' => $question->id, 'option_text' => 'False', 'is_correct' => $q['correct'] === 'False']);
        }

        // 2 Match
        $matchQuestions = [
            [
                'text'  => 'Match each country with its capital city.',
                'pairs' => [
                    ['left' => 'France',  'right' => 'Paris'],
                    ['left' => 'Japan',   'right' => 'Tokyo'],
                    ['left' => 'Germany', 'right' => 'Berlin'],
                ],
            ],
            [
                'text'  => 'Match each scientist with their discovery.',
                'pairs' => [
                    ['left' => 'Newton',   'right' => 'Gravity'],
                    ['left' => 'Einstein', 'right' => 'Relativity'],
                    ['left' => 'Darwin',   'right' => 'Evolution'],
                ],
            ],
        ];

        foreach ($matchQuestions as $i => $q) {
            $question = Question::create([
                'exam_id'       => $exam3->id,
                'question_text' => $q['text'],
                'question_type' => 'match',
                'marks'         => 1,
                'order'         => $i + 5,
            ]);
            foreach ($q['pairs'] as $pair) {
                Option::create([
                    'question_id' => $question->id,
                    'option_text' => $pair['left'],
                    'is_correct'  => true,
                    'match_pair'  => $pair['right'],
                ]);
            }
        }
    }
}
