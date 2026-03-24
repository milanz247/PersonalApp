<?php

namespace App\Console\Commands;

use App\Http\Controllers\RecurringTransactionController;
use App\Models\RecurringTransaction;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessRecurringTransactions extends Command
{
    protected $signature = 'recurring:process';

    protected $description = 'Process all active recurring transactions that are due';

    public function handle(): int
    {
        $today = Carbon::today();

        $due = RecurringTransaction::where('status', 'active')
            ->where('next_date', '<=', $today)
            ->get();

        if ($due->isEmpty()) {
            $this->info('No recurring transactions due today.');

            return self::SUCCESS;
        }

        $processed = 0;

        foreach ($due as $recurring) {
            try {
                DB::transaction(function () use ($recurring) {
                    RecurringTransactionController::executeRecurring($recurring);
                });
                $processed++;
                $this->line("  ✓ Processed: {$recurring->description} ({$recurring->type} — {$recurring->amount})");
            } catch (\Throwable $e) {
                $this->error("  ✗ Failed: {$recurring->description} — {$e->getMessage()}");
            }
        }

        $this->info("Done. Processed {$processed}/{$due->count()} recurring transactions.");

        return self::SUCCESS;
    }
}
