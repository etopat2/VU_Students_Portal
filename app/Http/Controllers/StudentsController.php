<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\StudyProgram;
use App\Models\StudySession;
use App\Models\StudySessionChangeRequest;
use App\Models\Student;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class StudentsController extends Controller
{
    public function index(Request $request): View|Response
    {
        $students = Student::query()
            ->with([
                'studyProgram:id,name',
                'studySession:id,name',
                'primaryCourse:id,code,title',
                'academicYear:id,name,year_level',
            ])
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $data = [
            'students' => $students,
            'studentCount' => Student::count(),
            'activeCount' => Student::where('status', 'Active')->count(),
            'programmeCount' => Student::query()->distinct()->count('study_program_id'),
            'sessionCount' => Student::query()->distinct()->whereNotNull('study_session_id')->count('study_session_id'),
        ];

        return $this->respond($request, 'students.index', $data, 'Students');
    }

    public function create(Request $request): View|Response
    {
        return $this->respond($request, 'students.createStudent', $this->formData(), 'Add student');
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        unset($validated['assignment_reason']);

        DB::transaction(function () use ($request, $validated): void {
            $course = Course::query()->findOrFail($validated['course_id']);
            $academicYear = AcademicYear::query()->findOrFail($validated['academic_year_id']);
            $student = Student::create([
                ...$validated,
                'programme' => $course->title,
                'year' => $academicYear->name,
            ]);

            $this->recordEnrollment($student, $validated, $request->user()->id);
        });

        ToastMagic::success('Student added successfully.');

        return redirect()->route('students.index');
    }

    public function edit(Request $request, Student $student): View|Response
    {
        return $this->respond($request, 'students.editStudent', $this->formData($student), 'Edit student');
    }

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $student, $validated): void {
            $student = Student::query()->lockForUpdate()->findOrFail($student->id);
            $course = Course::query()->findOrFail($validated['course_id']);
            $academicYear = AcademicYear::query()->findOrFail($validated['academic_year_id']);
            $currentSessionId = $student->study_session_id;
            $currentProgramId = $student->study_program_id;
            $sessionChanged = (int) $student->study_session_id !== (int) $validated['study_session_id'];
            $programChanged = (int) $student->study_program_id !== (int) $validated['study_program_id'];
            $enrollmentChanged = (int) $student->course_id !== (int) $validated['course_id']
                || $programChanged
                || (int) $student->academic_year_id !== (int) $validated['academic_year_id'];

            if ($enrollmentChanged) {
                $student->enrollments()
                    ->where('status', 'active')
                    ->update(['status' => 'transferred', 'ended_on' => today()]);
            }

            $assignmentReason = $validated['assignment_reason'] ?? null;
            unset($validated['assignment_reason']);
            $student->update([
                ...$validated,
                'programme' => $course->title,
                'year' => $academicYear->name,
            ]);

            if ($sessionChanged || $programChanged) {
                $student->studySessionChangeRequests()->where('status', 'pending')->update([
                    'status' => 'cancelled',
                    'reviewed_by' => $request->user()->id,
                    'review_notes' => 'Cancelled because a staff member updated the student record.',
                    'reviewed_at' => now(),
                ]);
                StudySessionChangeRequest::query()->create([
                    'student_id' => $student->id,
                    'requested_by' => $request->user()->id,
                    'current_study_session_id' => $currentSessionId,
                    'requested_study_session_id' => $student->study_session_id,
                    'current_study_program_id' => $currentProgramId,
                    'requested_study_program_id' => $student->study_program_id,
                    'reason' => $assignmentReason,
                    'status' => 'approved',
                    'reviewed_by' => $request->user()->id,
                    'review_notes' => 'Applied during student record update.',
                    'reviewed_at' => now(),
                    'is_strategic' => true,
                ]);
            }

            if ($enrollmentChanged) {
                $this->recordEnrollment($student, $validated, $request->user()->id);
            }
        });

        ToastMagic::success('Student updated successfully.');

        return redirect()->route('students.index');
    }

    public function destroy(Student $student): RedirectResponse
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'registrar']), 403);
        $student->delete();

        ToastMagic::success('Student deleted successfully.');

        return redirect()->route('students.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Student $student = null): array
    {
        return [
            'student' => $student,
            'studyPrograms' => StudyProgram::query()->where('is_active', true)->orderBy('name')->get(),
            'studySessions' => StudySession::query()->where('is_active', true)->orderBy('name')->get(),
            'courses' => Course::query()
                ->with(['studyPrograms:id,name', 'academicYears:id,course_id,name,year_level'])
                ->orderBy('title')
                ->get(['id', 'code', 'title']),
            'academicYears' => AcademicYear::query()
                ->orderBy('course_id')
                ->orderBy('year_level')
                ->get(['id', 'course_id', 'name', 'year_level']),
        ];
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function recordEnrollment(Student $student, array $validated, int $userId): void
    {
        Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $validated['course_id'],
            'study_program_id' => $validated['study_program_id'],
            'academic_year_id' => $validated['academic_year_id'],
            'created_by' => $userId,
            'started_on' => today(),
            'status' => 'active',
        ]);
    }

    private function respond(Request $request, string $view, array $data, string $title): View|Response
    {
        $page = view($view, $data);

        if ($request->ajax()) {
            return response($page->renderSections()['content'])
                ->header('X-Page-Title', $title);
        }

        return $page;
    }
}
