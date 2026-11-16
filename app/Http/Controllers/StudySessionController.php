<?php

namespace App\Http\Controllers;

use App\Models\StudyProgram;
use App\Models\StudySession;
use App\Models\StudySessionChangeRequest;
use App\Models\Student;
use App\Models\Enrollment;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudySessionController extends Controller
{
    public function index(Request $request): View|Response
    {
        $sessions = StudySession::query()
            ->withCount('students')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();
        $pendingRequestCount = StudySessionChangeRequest::query()->where('status', 'pending')->count();

        return $this->respond($request, 'study-sessions.index', compact('sessions', 'pendingRequestCount'), 'Study sessions');
    }

    public function create(Request $request): View|Response
    {
        return $this->respond($request, 'study-sessions.form', ['studySession' => null], 'Add study session');
    }

    public function store(Request $request): RedirectResponse
    {
        StudySession::query()->create($request->validate($this->rules()));
        ToastMagic::success('Study session created.');

        return redirect()->route('study-sessions.index');
    }

    public function edit(Request $request, StudySession $studySession): View|Response
    {
        return $this->respond($request, 'study-sessions.form', compact('studySession'), 'Edit study session');
    }

    public function update(Request $request, StudySession $studySession): RedirectResponse
    {
        $studySession->update($request->validate($this->rules($studySession)));
        ToastMagic::success('Study session updated.');

        return redirect()->route('study-sessions.index');
    }

    public function destroy(StudySession $studySession): RedirectResponse
    {
        $hasRequestHistory = StudySessionChangeRequest::query()
            ->where(function ($query) use ($studySession): void {
                $query->where('current_study_session_id', $studySession->id)
                    ->orWhere('requested_study_session_id', $studySession->id);
            })
            ->exists();

        if ($studySession->students()->exists() || $hasRequestHistory) {
            throw ValidationException::withMessages([
                'study_session' => 'This session has students or change history. Deactivate it to prevent new assignments while preserving the record.',
            ]);
        }

        $studySession->delete();
        ToastMagic::success('Study session deleted.');

        return redirect()->route('study-sessions.index');
    }

    public function assignments(Request $request): View|Response
    {
        $students = Student::query()
            ->with(['studySession:id,name', 'studyProgram:id,name', 'primaryCourse.studyPrograms:id,name,is_active'])
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();
        $studySessions = StudySession::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'audience']);

        return $this->respond($request, 'study-sessions.assignments', compact('students', 'studySessions'), 'Study assignments');
    }

    public function assign(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'study_session_id' => ['nullable', 'integer', 'exists:study_sessions,id', 'required_without:study_program_id'],
            'study_program_id' => ['nullable', 'integer', 'exists:study_programs,id', 'required_without:study_session_id'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        return DB::transaction(function () use ($request, $student, $validated): RedirectResponse {
            $student = Student::query()->lockForUpdate()->findOrFail($student->id);
            $newSessionId = $validated['study_session_id'] ?? $student->study_session_id;
            $newProgramId = $validated['study_program_id'] ?? $student->study_program_id;

            if ((int) $newSessionId === (int) $student->study_session_id
                && (int) $newProgramId === (int) $student->study_program_id) {
                throw ValidationException::withMessages(['study_session_id' => 'Choose a different session or program.']);
            }

            if ($newSessionId) {
                $session = StudySession::query()->whereKey($newSessionId)->where('is_active', true)->first();
                if (! $session) {
                    throw ValidationException::withMessages(['study_session_id' => 'Choose an active study session.']);
                }
                if ($session->audience === 'in_service_teachers' && ! $student->is_in_service_teacher) {
                    throw ValidationException::withMessages(['study_session_id' => 'The student must be marked as an in-service teacher before assignment.']);
                }
            }

            if ($newProgramId && ! StudyProgram::query()
                ->whereKey($newProgramId)
                ->where('is_active', true)
                ->whereHas('courses', fn ($query) => $query->whereKey($student->course_id))
                ->exists()) {
                throw ValidationException::withMessages(['study_program_id' => 'Choose an active program offered for the student’s course.']);
            }

            $currentSessionId = $student->study_session_id;
            $currentProgramId = $student->study_program_id;
            $programChanged = (int) $newProgramId !== (int) $currentProgramId;
            $student->update([
                'study_session_id' => $newSessionId,
                'study_program_id' => $newProgramId,
            ]);

            if ($programChanged && $student->course_id && $student->academic_year_id) {
                $student->enrollments()->where('status', 'active')->update([
                    'status' => 'transferred',
                    'ended_on' => today(),
                ]);
                Enrollment::query()->create([
                    'student_id' => $student->id,
                    'course_id' => $student->course_id,
                    'study_program_id' => $newProgramId,
                    'academic_year_id' => $student->academic_year_id,
                    'created_by' => $request->user()->id,
                    'started_on' => today(),
                    'status' => 'active',
                ]);
            }

            $student->studySessionChangeRequests()->where('status', 'pending')->update([
                'status' => 'cancelled',
                'reviewed_by' => $request->user()->id,
                'review_notes' => 'Cancelled because an administrator applied a strategic reassignment.',
                'reviewed_at' => now(),
            ]);

            StudySessionChangeRequest::query()->create([
                'student_id' => $student->id,
                'requested_by' => $request->user()->id,
                'current_study_session_id' => $currentSessionId,
                'requested_study_session_id' => $newSessionId,
                'current_study_program_id' => $currentProgramId,
                'requested_study_program_id' => $newProgramId,
                'reason' => $validated['reason'],
                'status' => 'approved',
                'reviewed_by' => $request->user()->id,
                'review_notes' => 'Strategic reassignment by an administrator.',
                'reviewed_at' => now(),
                'is_strategic' => true,
            ]);

            ToastMagic::success('Student study assignment updated and recorded.');

            return redirect()->route('study-sessions.assignments');
        });
    }

    /** @return array<string, array<int, string|\Illuminate\Validation\Rules\Unique>> */
    private function rules(?StudySession $studySession = null): array
    {
        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('study_sessions', 'code')->ignore($studySession)],
            'name' => ['required', 'string', 'max:80', Rule::unique('study_sessions', 'name')->ignore($studySession)],
            'audience' => ['required', Rule::in(['all_students', 'in_service_teachers'])],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    private function respond(Request $request, string $view, array $data, string $title): View|Response
    {
        $page = view($view, $data);
        if ($request->ajax()) {
            return response($page->renderSections()['content'])->header('X-Page-Title', $title);
        }

        return $page;
    }
}
