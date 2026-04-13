<?php

namespace App\Http\Controllers\ParentPanel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parent\StoreStudentRequest;
use App\Models\ExamAttempt;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(): View
    {
        $children = auth()->user()->childProfiles()->with('user')->get();

        $examStats = [];
        foreach ($children as $child) {
            $examStats[$child->user_id] = [
                'total'  => ExamAttempt::where('student_id', $child->user_id)->whereNotNull('submitted_at')->count(),
                'latest' => ExamAttempt::where('student_id', $child->user_id)->whereNotNull('submitted_at')->with('exam')->latest('submitted_at')->first(),
            ];
        }

        return view('parent.students.index', compact('children', 'examStats'));
    }

    public function create(): View
    {
        $classLevels = StudentProfile::CLASS_LEVELS;
        return view('parent.students.create', compact('classLevels'));
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $studentNumber = null;

        DB::transaction(function () use ($request, &$studentNumber) {
            $user = User::create([
                'name'      => $request->name,
                'email'     => null, // Students log in by student number + PIN, no email needed
                'password'  => Hash::make(\Illuminate\Support\Str::random(16)),
                'is_active' => true,
            ]);
            $user->assignRole('student');

            $studentNumber = $this->generateStudentNumber();

            StudentProfile::create([
                'user_id'        => $user->id,
                'parent_id'      => auth()->id(),
                'student_number' => $studentNumber,
                'pin'            => Hash::make($request->pin),
                'class_level'    => $request->class_level,
            ]);
        });

        return redirect()->route('parent.students.create')
            ->with('student_created', [
                'name'           => $request->name,
                'student_number' => $studentNumber,
            ]);
    }

    public function resetPin(Request $request, StudentProfile $profile): RedirectResponse
    {
        abort_unless($profile->parent_id === auth()->id(), 403);

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'pin' => ['required', 'digits:4', 'confirmed'],
        ], [
            'pin.digits'    => 'PIN must be exactly 4 digits.',
            'pin.confirmed' => 'PIN confirmation does not match.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->with('_pin_profile_id', $request->input('_pin_profile_id'));
        }

        $profile->update(['pin' => Hash::make($request->pin)]);

        return back()->with('success', "PIN updated for {$profile->user->name}.");
    }

    public function results(StudentProfile $profile): View
    {
        // Ensure this profile belongs to the authenticated parent
        abort_unless($profile->parent_id === auth()->id(), 403);

        $attempts = ExamAttempt::where('student_id', $profile->user_id)
            ->whereNotNull('submitted_at')
            ->with('exam')
            ->latest('submitted_at')
            ->get()
            ->unique('exam_id'); // Only latest per exam

        return view('parent.students.results', compact('profile', 'attempts'));
    }

    protected function generateStudentNumber(): string
    {
        do {
            $date   = now()->format('Ymd');
            $random = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $number = "STU-{$date}-{$random}";
        } while (StudentProfile::where('student_number', $number)->exists());

        return $number;
    }
}
