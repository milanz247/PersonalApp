<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransferController extends Controller
{
    /**
     * Execute an internal transfer between two accounts.
     *
     * Actions performed inside a single DB transaction:
     *  1. Create a 'transfer' transaction record for the principal amount.
     *  2. If a fee > 0, create a separate 'expense' transaction record for the fee,
     *     categorised as 'Bank/ATM Fees'.
     *  3. Debit source account by (amount + fee).
     *  4. Credit destination account by amount only.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'from_account_id' => ['required', 'integer', 'exists:accounts,id', 'different:to_account_id'],
            'to_account_id'   => ['required', 'integer', 'exists:accounts,id'],
            'amount'          => ['required', 'numeric', 'min:0.01'],
            'fee'             => ['nullable', 'numeric', 'min:0'],
            'date'            => ['required', 'date'],
            'note'            => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $fee  = (float) ($validated['fee'] ?? 0);

        // Authorise — both accounts must belong to the authenticated user
        $fromAccount = Account::where('id', $validated['from_account_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        $toAccount = Account::where('id', $validated['to_account_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        DB::transaction(function () use ($user, $fromAccount, $toAccount, $validated, $fee) {

            $amount = (float) $validated['amount'];
            $date   = $validated['date'];
            $note   = $validated['note'] ?? null;

            // Re-fetch source account with a row-level lock to prevent concurrent over-spend
            $fromAccount = Account::where('id', $fromAccount->id)
                ->lockForUpdate()
                ->firstOrFail();

            $required = $amount + $fee;
            if ((float) $fromAccount->balance < $required) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'amount' => 'Insufficient balance. Available: '
                        . number_format((float) $fromAccount->balance, 2)
                        . ', required: '
                        . number_format($required, 2) . ' (amount + fee).',
                ]);
            }

            // Look up the system 'Bank Transfer' category
            $transferCategory = Category::where('type', 'transfer')
                ->where('is_system', true)
                ->first();

            // --- Action 1: transfer record for the principal amount ---
            Transaction::create([
                'user_id'         => $user->id,
                'from_account_id' => $fromAccount->id,
                'to_account_id'   => $toAccount->id,
                'category_id'     => $transferCategory?->id,
                'type'            => 'transfer',
                'amount'          => $amount,
                'fee'             => $fee,
                'date'            => $date,
                'note'            => $note,
            ]);

            // --- Action 2 & 3: expense record for the fee (only when fee > 0) ---
            if ($fee > 0) {
                $feeCategory = Category::firstOrCreate(
                    ['name' => 'Bank/ATM Fees', 'user_id' => null],
                    ['type' => 'expense', 'is_system' => true]
                );

                Transaction::create([
                    'user_id'         => $user->id,
                    'from_account_id' => $fromAccount->id,
                    'to_account_id'   => null,
                    'category_id'     => $feeCategory->id,
                    'type'            => 'expense',
                    'amount'          => $fee,
                    'fee'             => 0,
                    'date'            => $date,
                    'note'            => 'Transfer fee',
                ]);
            }

            // --- Action 4: update balances ---
            // Source decreases by amount + fee
            $fromAccount->decrement('balance', $amount + $fee);

            // Destination increases by amount only
            $toAccount->increment('balance', $amount);
        });

        return redirect()->route('accounts.index')
            ->with('success', 'Transfer completed successfully.');
    }
}
