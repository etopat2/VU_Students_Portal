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
use Illuminate\Validation\ValidationException;

class StudySessionChangeRequestController extends Controller
{
    public function studentIndex(Request $request): View|Response
    {
        $student = Student::query()
            ->with(['studySession', 'studyProgram', 'primaryCourse.studyPrograms'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
        $pendingRequest = $student->studySessionChangeRequests()->where('status', 'pending')->latest()->first();
        $history = $student->studySessionChangeRequests()
            ->with(['currentStudySession', 'requestedStudySession', 'currentStudyProgram', 'requestedStudyProgram', 'reviewer'])
            ->latest()
            ->limit(10)
            ->get();
        $studySessions = StudySession::query()
            ->where('is_active', true)
            ->where(function ($query) use ($student): void {
                $query->where('audience', 'all_students');
                if ($student->is_in_service_teacher) {
                    $query->orWhere('audience', 'in_service_teachers');
                }
            })
            ->orderBy('name')
            ->get();
        $studyPrograms = $student->primaryCourse?->studyPrograms()
            ->where('study_programs.is_active', true)
            ->orderBy('study_programs.name')
            ->get(['study_programs.id', 'study_programs.name']) ?? collect();

        return $this->respond($request, 'study-sessions.student', compact(
            'student', 'pendingRequest', 'history', 'studySessions', 'studyPrograms',
        ), 'My study assignment');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'study_session_id' => ['nullable', 'integer', 'exists:study_sessions,id'],
            'study_program_id' => ['nullable', 'integer', 'exists:study_programs,id'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $validated): void {
            $student = Student::query()->where('user_id', $request->user()->id)->lockForUpdate()->firstOrFail();
            if ($student->studySessionChangeRequests()->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages(['request' => 'You already have a change request waiting for review.']);
            }

            $sessionId = $validated['study_session_id'] ?? null;
            $programId = $validated['study_program_id'] ?? null;
            if ($sessionId === null && $programId === null) {
                throw ValidationException::withMessages(['study_session_id' => 'Select a new study session, study program, or both.']);
            }
            if ($sessionId !== null && (int) $sessionId === (int) $student->study_session_id) {
                throw ValidationException::withMessages(['study_session_id' => 'Choose a session different from your current one.']);
            }
            if ($programId !== null && (int) $programId === (int) $student->study_program_id) {
                throw ValidationException::withMessages(['study_program_id' => 'Choose a program different from your current one.']);
            }
            $session = $sessionId !== null
                ? StudySession::query()->whereKey($sessionId)->where('is_active', true)->first()
                : null;
            if ($sessionId !== null && ! $session) {
                throw ValidationException::withMessages(['study_session_id' => 'Choose an active study session.']);
            }
            if ($session?->audience === 'in_service_teachers' && ! $student->is_in_service_teacher) {
                throw ValidationException::withMessages(['study_session_id' => 'This session is available only to students marked as in-service teachers.']);
            }
            if ($programId !== null && ! StudyProgram::query()
                ->whereKey($programId)
                ->where('is_active', true)
                ->whereHas('courses', fn ($query) => $query->whereKey($student->course_id))
                ->exists()) {
                throw ValidationException::withMessages(['study_program_id' => 'Choose an active program offered for your course.']);
            }

            StudySessionChangeRequest::query()->create([
                'student_id' => $student->id,
                'requested_by' => $request->user()->id,
                'current_study_session_id' => $student->study_session_id,
                'requested_study_session_id' => $sessionId,
                'current_study_program_id' => $student->study_program_id,
                'requested_study_program_id' => $programId,
                'reason' => $validated['reason'],
                'status' => 'pending',
            ]);
        });

        ToastMagic::success('Your study change request was sent for review.');

        return redirect()->route('student.study-session');
    }

    public function index(Request $request): View|Response
    {
        $requests = StudySessionChangeRequest::query()
            ->with([
                'student.primaryCourse:id,title',
                'requester:id,name',
                'currentStudySession:id,name',
                'requestedStudySession:id,name',
                'currentStudyProgram:id,name',
                'requestedStudyProgram:id,name',
                'reviewer:id,name',
            ])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return $this->respond($request, 'study-sessions.requests', compact('requests'), 'Study change requests');
    }

    public function review(Request $request, StudySessionChangeRequest $changeRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'review_notes' => ['nullable', 'string', 'max:1000', 'required_if:status,rejected'],
        ]);

        DB::transaction(function () use ($request, $changeRequest, $validated): void {
            $changeRequest = StudySessionChangeRequest::query()->lockForUpdate()->findOrFail($changeRequest->id);
            if ($changeRequest->status !== 'pending') {
                throw ValidationException::withMessages(['request' => 'This request has already been reviewed.']);
            }

            if ($validated['status'] === 'approved') {
                $student = Student::query()->lockForUpdate()->findOrFail($changeRequest->student_id);

                if ((int) $student->study_session_id !== (int) $changeRequest->current_study_session_id
                    || (int) $student->study_program_id !== (int) $changeRequest->current_study_program_id) {
                    throw ValidationException::withMessages(['request' => 'The student’s assignment changed after this request was submitted. Review the current assignment before taking action.']);
                }

                $updates = [];

                if ($changeRequest->requested_study_session_id !== null) {
                    $session = StudySession::query()
                        ->whereKey($changeRequest->requested_study_session_id)
                        ->where('is_active', true)
                        ->first();
                    if (! $session) {
                        throw ValidationException::withMessages(['request' => 'The requested study session is no longer active.']);
                    }
                    if ($session->audience === 'in_service_teachers' && ! $student->is_in_service_teacher) {
                        throw ValidationException::withMessages(['request' => 'The student is not marked as an in-service teacher.']);
                    }
                    $updates['study_session_id'] = $changeRequest->requested_study_session_id;
                }

                if ($changeRequest->requested_study_program_id !== null) {
                    $offered = StudyProgram::query()
                        ->whereKey($changeRequest->requested_study_program_id)
                        ->where('is_active', true)
                        ->whereHas('courses', fn ($query) => $query->whereKey($student->course_id))
                        ->exists();
                    if (! $offered) {
                        throw ValidationException::withMessages(['request' => 'The requested program is no longer offered for this course.']);
                    }
                    $updates['study_program_id'] = $changeRequest->requested_study_program_id;
                }

                if ($updates === []) {
                    throw ValidationException::withMessages(['request' => 'This request no longer has an active change to apply.']);
                }

                $programChanged = array_key_exists('study_program_id', $updates)
                    && (int) $updates['study_program_id'] !== (int) $student->study_program_id;
                $student->update($updates);

                if ($programChanged && $student->course_id && $student->academic_year_id) {
                    $student->enrollments()->where('status', 'active')->update([
                        'status' => 'transferred',
                        'ended_on' => today(),
                    ]);
                    Enrollment::query()->create([
                        'student_id' => $student->id,
                        'course_id' => $student->course_id,
                        'study_program_id' => $student->study_program_id,
                        'academic_year_id' => $student->academic_year_id,
                        'created_by' => $request->user()->id,
                        'started_on' => today(),
                        'status' => 'active',
                    ]);
                }
            }

            $changeRequest->update([
                'status' => $validated['status'],
                'review_notes' => $validated['review_notes'] ?? null,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);
        });

        ToastMagic::success('Study change request '.($validated['status'] === 'approved' ? 'approved.' : 'declined.'));

        return redirect()->route('study-session-requests.index');
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
