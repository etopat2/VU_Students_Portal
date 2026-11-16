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
        Schema::create('enrollments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('started_on');
            $table->date('ended_on')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->index(['student_id', 'status']);
        });

        Schema::create('semester_registrations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('academic_sessions')->restrictOnDelete();
            $table->foreignId('enrollment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('registered');
            $table->timestamp('registered_at')->useCurrent();
            $table->timestamps();
            $table->unique(['student_id', 'session_id']);
        });

        Schema::create('student_module_registrations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('semester_registration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('registered');
            $table->timestamps();
            $table->unique(['semester_registration_id', 'module_id'], 'student_module_session_unique');
            $table->index(['module_id', 'status']);
        });

        Schema::create('teaching_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lecturer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('allocated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['lecturer_id', 'module_id', 'session_id'], 'teaching_assignment_unique');
            $table->index(['module_id', 'session_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teaching_allocations');
        Schema::dropIfExists('student_module_registrations');
        Schema::dropIfExists('semester_registrations');
        Schema::dropIfExists('enrollments');
    }
};
