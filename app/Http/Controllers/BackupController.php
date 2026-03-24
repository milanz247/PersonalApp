<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class BackupController extends Controller
{
    /**
     * Trigger a manual database backup.
     */
    public function runBackup(Request $request): RedirectResponse
    {
        try {
            Artisan::call('backup:run', [
                '--only-db' => true,
            ]);

            // Store the backup timestamp in user settings or a simple file
            $request->user()->update([
                'last_backup_at' => now(),
            ]);

            return back()->with('success', 'Database backup completed successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }
}
