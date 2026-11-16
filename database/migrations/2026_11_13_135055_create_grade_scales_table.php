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
        Schema::create('grade_scales', function (Blueprint $table): void {
            $table->id();
            $table->string('letter_grade', 3)->unique();
            $table->decimal('minimum_mark', 5, 2);
            $table->decimal('grade_point', 4, 2);
            $table->timestamps();
            $table->index('minimum_mark');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_scales');
    }
};
