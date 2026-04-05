<?php

namespace App\Http\Controllers;

use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RecurringTransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $recurring = $request->user()
            ->recurringTransactions()
            ->with(['account:id,name', 'category:id,name,color'])
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Recurring/Index', [
            'recurringTransactions' => $recurring,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'account_id'  => ['required', 'integer', 'exists:accounts,id'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:500'],
            'type'        => ['required', 'in:income,expense'],
            'frequency'   => ['required', 'in:daily,weekly,monthly,yearly'],
            'start_date'  => ['required', 'date'],
        ]);

        $request->user()->recurringTransactions()->create([
            ...$validated,
            'next_date' => $validated['start_date'],
        ]);

        return back()->with('success', 'Recurring transaction created successfully.');
    }

    public function toggleStatus(Request $request, RecurringTransaction $recurringTransaction): RedirectResponse
    {
        abort_if($recurringTransaction->user_id !== $request->user()->id, 403);

        $recurringTransaction->update([
            'status' => $recurringTransaction->status === 'active' ? 'paused' : 'active',
        ]);

        $label = $recurringTransaction->status === 'active' ? 'activated' : 'paused';

        return back()->with('success', "Recurring transaction {$label}.");
    }

    public function destroy(Request $request, RecurringTransaction $recurringTransaction): RedirectResponse
    {
        abort_if($recurringTransaction->user_id !== $request->user()->id, 403);

        $recurringTransaction->delete();

        return back()->with('success', 'Recurring transaction deleted.');
    }

    /**
     * Manually trigger execution of a single recurring transaction ("Run Now").
     */
    public function runNow(Request $request, RecurringTransaction $recurringTransaction): RedirectResponse
    {
        abort_if($recurringTransaction->user_id !== $request->user()->id, 403);

        DB::transaction(function () use ($recurringTransaction) {
            $this->executeRecurring($recurringTransaction);
        });

        return back()->with('success', 'Transaction executed successfully.');
    }

    /**
     * Execute a single recurring transaction: create transaction, update balance, advance next_date.
     */
    public static function executeRecurring(RecurringTransaction $recurring): void
    {
        // Create the actual transaction
        Transaction::create([
            'user_id'         => $recurring->user_id,
            'from_account_id' => $recurring->type === 'expense' ? $recurring->account_id : null,
            'to_account_id'   => $recurring->type === 'income' ? $recurring->account_id : null,
            'category_id'     => $recurring->category_id,
            'type'            => $recurring->type,
            'amount'          => $recurring->amount,
            'fee'             => 0,
            'date'            => Carbon::today(),
            'note'            => $recurring->description,
        ]);

        // Update account balance — lock the row to prevent concurrent reads of stale balance
        $account = Account::where('id', $recurring->account_id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($recurring->type === 'expense') {
            if ((float) $account->balance < (float) $recurring->amount) {
                throw new \RuntimeException(
                    "Insufficient balance for recurring expense '{$recurring->description}'."
                    . ' Available: ' . number_format((float) $account->balance, 2)
                    . ', required: ' . number_format((float) $recurring->amount, 2) . '.'
                );
            }
            $account->decrement('balance', $recurring->amount);
        } else {
            $account->increment('balance', $recurring->amount);
        }

        // Advance to next date
        $recurring->update([
            'last_executed_at' => Carbon::now(),
            'next_date'        => $recurring->calculateNextDate(),
        ]);
    }
}
