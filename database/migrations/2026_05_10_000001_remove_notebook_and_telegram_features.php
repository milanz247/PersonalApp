<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration removes the Personal Notebook (Notes) feature and Telegram integration
     * that were completely removed from the codebase.
     */
    public function up(): void
    {
        // Drop notes table if it exists
        Schema::dropIfExists('notes');

        // Remove telegram-related columns from users table
        if (Schema::hasColumn('users', 'telegram_bot_token') ||
            Schema::hasColumn('users', 'telegram_chat_id') ||
            Schema::hasColumn('users', 'telegram_webhook_secret')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn([
                    'telegram_bot_token',
                    'telegram_chat_id',
                    'telegram_webhook_secret',
                ]);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: Reversing this migration will NOT recreate the notes table or telegram columns
        // as the feature has been completely removed from the codebase.
        // To restore, you would need to check out an earlier version of the code.
    }
};
