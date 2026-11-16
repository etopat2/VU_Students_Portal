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
        Schema::table('courses', function (Blueprint $table): void {
            $table->foreignId('lecturer_id')->nullable()->after('department_id')
                ->constrained('lecturers')->nullOnDelete();
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->foreignId('study_program_id')->nullable()->after('course_id')
                ->constrained('study_programs')->nullOnDelete();
            $table->foreignId('academic_year_id')->nullable()->after('study_program_id')
                ->constrained()->nullOnDelete();
        });

        Schema::table('enrollments', function (Blueprint $table): void {
            $table->foreignId('study_program_id')->nullable()->after('course_id')
                ->constrained('study_programs')->nullOnDelete();
            $table->foreignId('academic_year_id')->nullable()->after('study_program_id')
                ->constrained()->nullOnDelete();
        });

        Schema::create('course_study_program', function (Blueprint $table): void {
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('study_program_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['course_id', 'study_program_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_study_program');
        Schema::table('enrollments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('academic_year_id');
            $table->dropConstrainedForeignId('study_program_id');
        });
        Schema::table('students', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('academic_year_id');
            $table->dropConstrainedForeignId('study_program_id');
        });
        Schema::table('courses', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('lecturer_id');
        });
    }
};
