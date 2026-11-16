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
        Schema::create('cash_registers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('opened_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('register_date');
            $table->decimal('opening_balance', 12, 2);
            $table->decimal('expected_balance', 12, 2)->nullable();
            $table->decimal('closing_balance', 12, 2)->nullable();
            $table->decimal('variance', 12, 2)->nullable();
            $table->string('status', 20)->default('open');
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->unique(['register_date', 'opened_by']);
            $table->index(['register_date', 'status']);
        });

        Schema::create('cash_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cash_register_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('expense_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->string('transaction_type', 20);
            $table->string('receipt_number', 50)->unique();
            $table->decimal('amount', 12, 2);
            $table->string('description');
            $table->timestamp('posted_at')->useCurrent();
            $table->timestamps();
            $table->index(['cash_register_id', 'posted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
        Schema::dropIfExists('cash_registers');
    }
};
