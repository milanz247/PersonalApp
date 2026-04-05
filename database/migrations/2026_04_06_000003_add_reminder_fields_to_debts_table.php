<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->string('contact_email')->nullable()->after('description');
            $table->string('contact_phone')->nullable()->after('contact_email');
            $table->boolean('initial_notification_sent')->default(false)->after('contact_phone');
            $table->timestamp('last_reminder_sent_at')->nullable()->after('initial_notification_sent');
        });
    }

    public function down(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->dropColumn(['contact_email', 'contact_phone', 'initial_notification_sent', 'last_reminder_sent_at']);
        });
    }
};
