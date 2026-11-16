<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('gateway', 40)->nullable()->after('reference');
            $table->string('gateway_transaction_id', 100)->nullable()->unique()->after('gateway');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique(['gateway_transaction_id']);
            $table->dropColumn(['gateway', 'gateway_transaction_id']);
        });
    }
};
