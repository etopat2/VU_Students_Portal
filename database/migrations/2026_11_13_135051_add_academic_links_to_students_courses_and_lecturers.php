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
        Schema::table('courses', function (Blueprint $table): void {
            $table->foreignId('department_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->foreignId('course_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->string('clearance_status', 20)->default('cleared')->after('status');
        });

        Schema::table('lecturers', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::create('role_user', function (Blueprint $table): void {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['role_id', 'user_id']);
            $table->index('user_id');
        });

        DB::table('users')
            ->whereIn('role', ['admin', 'registrar', 'accountant', 'lecturer', 'student'])
            ->orderBy('id')
            ->get(['id', 'role'])
            ->each(function (object $user): void {
                $roleId = DB::table('roles')->where('name', $user->role)->value('id');

                if ($roleId !== null) {
                    DB::table('role_user')->insert([
                        'user_id' => $user->id,
                        'role_id' => $roleId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });

        Schema::create('course_module', function (Blueprint $table): void {
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['course_id', 'module_id']);
            $table->index('module_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_module');
        Schema::dropIfExists('role_user');
        Schema::table('lecturers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('user_id');
        });
        Schema::table('students', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('course_id');
            $table->dropColumn('clearance_status');
        });
        Schema::table('courses', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('department_id');
        });
    }
};
