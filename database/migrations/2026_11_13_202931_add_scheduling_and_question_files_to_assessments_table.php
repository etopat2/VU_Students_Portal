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
        Schema::table('assessments', function (Blueprint $table): void {
            $table->timestamp('starts_at')->nullable()->after('due_date');
            $table->timestamp('ends_at')->nullable()->after('starts_at');
            $table->string('question_file_path')->nullable()->after('ends_at');
            $table->string('question_file_original_name')->nullable()->after('question_file_path');
            $table->index('starts_at');
        });

        Schema::create('assessment_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_module_registration_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->longText('answer_content')->nullable();
            $table->string('answer_file_path')->nullable();
            $table->string('answer_file_original_name')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->unique(
                ['assessment_id', 'student_module_registration_id'],
                'assessment_submission_registration_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_submissions');

        Schema::table('assessments', function (Blueprint $table): void {
            $table->dropIndex(['starts_at']);
            $table->dropColumn([
                'starts_at',
                'ends_at',
                'question_file_path',
                'question_file_original_name',
            ]);
        });
    }
};
