<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('lecturer_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('assessment_type', 20);
            $table->decimal('max_score', 7, 2);
            $table->decimal('weight_percentage', 5, 2);
            $table->date('due_date')->nullable();
            $table->timestamps();
            $table->unique(['module_id', 'session_id', 'name']);
            $table->index(['module_id', 'session_id', 'assessment_type']);
        });

        Schema::create('assessment_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_module_registration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('score', 7, 2);
            $table->text('feedback')->nullable();
            $table->timestamp('entered_at')->useCurrent();
            $table->timestamps();
            $table->unique(
                ['student_module_registration_id', 'assessment_id'],
                'assessment_result_registration_unique',
            );
            $table->index('assessment_id');
        });

        Schema::create('final_module_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_module_registration_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('coursework_score', 7, 2)->default(0);
            $table->decimal('exam_score', 7, 2)->default(0);
            $table->decimal('final_mark', 7, 2);
            $table->string('letter_grade', 3);
            $table->decimal('grade_point', 4, 2);
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('timetable_slots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lecturer_id')->constrained()->restrictOnDelete();
            $table->foreignId('venue_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->timestamps();
            $table->index(['session_id', 'weekday', 'starts_at']);
            $table->index(['venue_id', 'weekday', 'starts_at']);
        });

        Schema::create('attendance_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('timetable_slot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20);
            $table->timestamp('recorded_at')->useCurrent();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'timetable_slot_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('timetable_slots');
        Schema::dropIfExists('final_module_results');
        Schema::dropIfExists('assessment_results');
        Schema::dropIfExists('assessments');
    }
};
