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
        Schema::create('institutions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code', 30)->unique();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('address')->nullable();
            $table->timestamps();
        });

        Schema::create('faculties', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 30)->unique();
            $table->timestamps();
            $table->unique(['institution_id', 'name']);
        });

        Schema::create('departments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('faculty_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 30)->unique();
            $table->timestamps();
            $table->unique(['faculty_id', 'name']);
        });

        Schema::create('academic_years', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedTinyInteger('year_level');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->timestamps();
            $table->unique(['course_id', 'year_level']);
        });

        Schema::create('academic_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedTinyInteger('number');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_open')->default(false);
            $table->timestamps();
            $table->unique(['academic_year_id', 'number']);
        });

        Schema::create('modules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->string('code', 30)->unique();
            $table->string('title');
            $table->unsignedTinyInteger('credit_units');
            $table->unsignedTinyInteger('year_level');
            $table->unsignedTinyInteger('session_number');
            $table->timestamps();
            $table->index(['year_level', 'session_number']);
        });

        Schema::create('venues', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('building')->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->timestamps();
            $table->unique(['building', 'name']);
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 30)->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        $timestamp = now();
        DB::table('roles')->insert(array_map(
            fn (string $name): array => [
                'name' => $name,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            ['admin', 'registrar', 'accountant', 'lecturer', 'student'],
        ));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
        Schema::dropIfExists('venues');
        Schema::dropIfExists('modules');
        Schema::dropIfExists('academic_sessions');
        Schema::dropIfExists('academic_years');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('faculties');
        Schema::dropIfExists('institutions');
    }
};
