<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('study_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 80)->unique();
            $table->string('audience', 30)->default('all_students');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $timestamp = now();
        DB::table('study_sessions')->insert([
            ['code' => 'DAY', 'name' => 'Day', 'audience' => 'all_students', 'description' => 'Weekday daytime study schedule.', 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['code' => 'WEEKEND', 'name' => 'Weekend', 'audience' => 'all_students', 'description' => 'Weekend study schedule.', 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['code' => 'EVENING', 'name' => 'Evening', 'audience' => 'all_students', 'description' => 'Evening study schedule for learners balancing work and study.', 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['code' => 'HOL-IN-SERVICE', 'name' => 'Holiday (In-service Teachers)', 'audience' => 'in_service_teachers', 'description' => 'Holiday study program designed for in-service teachers.', 'is_active' => true, 'created_at' => $timestamp, 'updated_at' => $timestamp],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_sessions');
    }
};
