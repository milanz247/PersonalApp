<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('currency_symbol', 10)->default('Rs.')->after('email');
            $table->string('currency_code', 10)->default('LKR')->after('currency_symbol');
            $table->string('timezone')->default('Asia/Colombo')->after('currency_code');
            $table->string('date_format', 20)->default('DD/MM/YYYY')->after('timezone');
            $table->string('avatar')->nullable()->after('date_format');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['currency_symbol', 'currency_code', 'timezone', 'date_format', 'avatar']);
        });
    }
};
