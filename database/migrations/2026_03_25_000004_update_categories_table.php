<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Global system categories have user_id = null (shared across all users)
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->boolean('is_system')->default(false)->after('type');
            $table->string('icon')->nullable()->after('is_system');
            $table->string('color')->nullable()->after('icon');
        });

        // Expand the type enum to include 'transfer' (MySQL requires a full column redefinition)
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE categories MODIFY type ENUM('income','expense','transfer','system') NOT NULL DEFAULT 'expense'");
        }
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'is_system', 'icon', 'color']);
        });
    }
};
