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
        Schema::create('study_session_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('current_study_session_id')->nullable()->constrained('study_sessions')->nullOnDelete();
            $table->foreignId('requested_study_session_id')->nullable()->constrained('study_sessions')->nullOnDelete();
            $table->foreignId('current_study_program_id')->nullable()->constrained('study_programs')->nullOnDelete();
            $table->foreignId('requested_study_program_id')->nullable()->constrained('study_programs')->nullOnDelete();
            $table->text('reason');
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->boolean('is_strategic')->default(false);
            $table->timestamps();
            $table->index(['student_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_session_change_requests');
    }
};
