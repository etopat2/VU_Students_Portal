<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterSemesterRequest;
use App\Models\Enrollment;
use App\Models\FeeStructure;
use App\Models\FinancialLedger;
use App\Models\Invoice;
use App\Models\SemesterRegistration;
use App\Models\Session;
use App\Models\Student;
use App\Support\Money;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SemesterRegistrationController extends Controller
{
    public function store(RegisterSemesterRequest $request, Student $student): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $student, $validated): void {
            $student = Student::query()->lockForUpdate()->findOrFail($student->id);
            $session = Session::query()->with('academicYear')->findOrFail($validated['session_id']);
            $feeStructure = FeeStructure::query()
                ->where('course_id', $student->course_id)
                ->where('academic_year_id', $session->academic_year_id)
                ->where('session_id', $session->id)
                ->firstOrFail();

            $amountInMinorUnits = Money::minorUnits((string) $feeStructure->tuition_amount)
                + Money::minorUnits((string) $feeStructure->functional_fees);

            if ($amountInMinorUnits <= 0) {
                throw ValidationException::withMessages([
                    'session_id' => 'The fee structure must have a positive total before registration.',
                ]);
            }

            $enrollment = Enrollment::query()
                ->where('student_id', $student->id)
                ->where('course_id', $student->course_id)
                ->where('status', 'active')
                ->latest('started_on')
                ->first();

            if (! $enrollment) {
                $enrollment = $student->enrollments()->create([
                    'course_id' => $student->course_id,
                    'created_by' => $request->user()->id,
                    'started_on' => today(),
                    'status' => 'active',
                ]);
            }

            $registration = SemesterRegistration::create([
                'student_id' => $student->id,
                'session_id' => $session->id,
                'enrollment_id' => $enrollment->id,
                'registered_by' => $request->user()->id,
                'status' => 'registered',
                'registered_at' => now(),
            ]);

            $registration->moduleRegistrations()->createMany(
                array_map(
                    fn (int $moduleId): array => ['module_id' => $moduleId, 'status' => 'registered'],
                    $validated['module_ids'],
                ),
            );

            $invoice = Invoice::create([
                'student_id' => $student->id,
                'session_id' => $session->id,
                'semester_registration_id' => $registration->id,
                'issued_by' => $request->user()->id,
                'invoice_number' => 'INV-'.Str::upper((string) Str::uuid()),
                'invoice_type' => 'semester',
                'description' => 'Tuition and functional fees for '.$session->name,
                'line_items' => [
                    ['description' => 'Tuition', 'amount' => (string) $feeStructure->tuition_amount],
                    ['description' => 'Functional fees', 'amount' => (string) $feeStructure->functional_fees],
                ],
                'amount' => Money::fromMinorUnits($amountInMinorUnits),
                'currency' => $feeStructure->currency,
                'status' => 'issued',
            ]);

            $previousBalance = FinancialLedger::query()
                ->where('student_id', $student->id)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->value('balance_after');
            $balanceInMinorUnits = Money::minorUnits((string) ($previousBalance ?? '0.00'))
                + $amountInMinorUnits;

            FinancialLedger::create([
                'student_id' => $student->id,
                'invoice_id' => $invoice->id,
                'recorded_by' => $request->user()->id,
                'debit' => Money::fromMinorUnits($amountInMinorUnits),
                'credit' => '0.00',
                'balance_after' => Money::fromMinorUnits($balanceInMinorUnits),
                'description' => 'Invoice '.$invoice->invoice_number,
                'posted_at' => now(),
            ]);

            $student->update([
                'clearance_status' => $balanceInMinorUnits > 0 ? 'uncleared' : 'cleared',
            ]);
        });

        ToastMagic::success('Semester registration and invoice created.');

        return back();
    }
}
