<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('assessment');
            $table->decimal('score', 5, 2);
            $table->string('letter_grade', 3);
            $table->string('term');
            $table->timestamps();
            $table->unique(['student_id', 'course_id', 'assessment', 'term'], 'grades_student_course_assessment_term_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
