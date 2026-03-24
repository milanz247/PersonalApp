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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['wallet', 'bank'])->default('bank');
            $table->string('bank_name')->nullable();
            $table->string('branch_name')->nullable();
            $table->string('account_number')->nullable();
            $table->decimal('balance', 15, 2)->default(0.00);
            $table->timestamps();
        });

        // Enforce "one wallet per user" at the DB level using a partial unique index (SQLite/PostgreSQL).
        // For MySQL, this is enforced via the UserObserver instead (MySQL does not support partial indexes natively).
        $driver = DB::getDriverName();

        if (in_array($driver, ['sqlite', 'pgsql'])) {
            DB::statement(
                "CREATE UNIQUE INDEX unique_wallet_per_user ON accounts (user_id) WHERE type = 'wallet'"
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
