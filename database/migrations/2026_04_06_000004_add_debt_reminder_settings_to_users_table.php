<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('debt_auto_send_initial')->default(false)->after('telegram_webhook_secret');
            $table->unsignedSmallInteger('debt_reminder_days_before')->default(2)->after('debt_auto_send_initial');
            $table->text('debt_initial_message')->nullable()->after('debt_reminder_days_before');
            $table->text('debt_reminder_message')->nullable()->after('debt_initial_message');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['debt_auto_send_initial', 'debt_reminder_days_before', 'debt_initial_message', 'debt_reminder_message']);
        });
    }
};
