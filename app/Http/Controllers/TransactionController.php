<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Debt;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $transactions = $request->user()
            ->transactions()
            ->with(['fromAccount:id,name', 'toAccount:id,name', 'category:id,name,type,is_system'])
            ->select('transactions.*')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
        ]);
    }

    /**
     * Record a new expense or income transaction and adjust the account balance.
     */
    public function store(Request $request): RedirectResponse
    {
        $type = $request->input('type');

        $rules = [
            'type'        => ['required', 'in:expense,income'],
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'date'        => ['required', 'date'],
            'note'        => ['nullable', 'string', 'max:500'],
        ];

        if ($type === 'expense') {
            $rules['from_account_id'] = ['required', 'integer', 'exists:accounts,id'];
        } else {
            $rules['to_account_id'] = ['required', 'integer', 'exists:accounts,id'];
        }

        $validated = $request->validate($rules);
        $user      = $request->user();
        $amount    = (float) $validated['amount'];

        DB::transaction(function () use ($user, $validated, $amount) {
            if ($validated['type'] === 'expense') {
                // Lock the row so concurrent requests cannot read stale balance
                $account = Account::where('id', $validated['from_account_id'])
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((float) $account->balance < $amount) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'amount' => 'Insufficient balance. Available: '
                            . number_format((float) $account->balance, 2)
                            . ', required: '
                            . number_format($amount, 2) . '.',
                    ]);
                }

                Transaction::create([
                    'user_id'         => $user->id,
                    'from_account_id' => $validated['from_account_id'],
                    'to_account_id'   => null,
                    'category_id'     => $validated['category_id'],
                    'type'            => 'expense',
                    'amount'          => $amount,
                    'fee'             => 0,
                    'date'            => $validated['date'],
                    'note'            => $validated['note'] ?? null,
                ]);

                $account->decrement('balance', $amount);
            } else {
                $account = Account::where('id', $validated['to_account_id'])
                    ->where('user_id', $user->id)
                    ->firstOrFail();

                Transaction::create([
                    'user_id'         => $user->id,
                    'from_account_id' => null,
                    'to_account_id'   => $validated['to_account_id'],
                    'category_id'     => $validated['category_id'],
                    'type'            => 'income',
                    'amount'          => $amount,
                    'fee'             => 0,
                    'date'            => $validated['date'],
                    'note'            => $validated['note'] ?? null,
                ]);

                $account->increment('balance', $amount);
            }
        });

        $label = $validated['type'] === 'expense' ? 'Expense' : 'Income';

        return back()->with('success', "{$label} added successfully.");
    }

    /**
     * Soft-delete a transaction and reverse its balance impact.
     * If linked to a debt, also update or delete the debt record.
     */
    public function destroy(Request $request, Transaction $transaction): RedirectResponse
    {
        abort_if($transaction->user_id !== $request->user()->id, 403);

        DB::transaction(function () use ($transaction) {
            $amount = (float) $transaction->amount;
            $fee    = (float) $transaction->fee;

            // ── 1. Reverse account balance ──────────────────────────────
            if ($transaction->type === 'expense' && $transaction->from_account_id) {
                Account::where('id', $transaction->from_account_id)->increment('balance', $amount);
            } elseif ($transaction->type === 'income' && $transaction->to_account_id) {
                Account::where('id', $transaction->to_account_id)->decrement('balance', $amount);
            } elseif ($transaction->type === 'transfer') {
                if ($transaction->from_account_id) {
                    Account::where('id', $transaction->from_account_id)->increment('balance', $amount + $fee);
                }
                if ($transaction->to_account_id) {
                    Account::where('id', $transaction->to_account_id)->decrement('balance', $amount);
                }
            }

            // ── 2. Handle linked debt record ────────────────────────────
            if ($transaction->debt_id) {
                $debt = Debt::find($transaction->debt_id);

                if ($debt) {
                    // Check if this is the original debt-creation transaction
                    // (the earliest transaction linked to this debt)
                    $isOriginal = !Transaction::where('debt_id', $debt->id)
                        ->where('id', '<', $transaction->id)
                        ->exists();

                    if ($isOriginal) {
                        // Deleting the original debt → soft-delete all related
                        // transactions and the debt record itself
                        Transaction::where('debt_id', $debt->id)
                            ->where('id', '!=', $transaction->id)
                            ->each(function (Transaction $related) {
                                $relatedAmount = (float) $related->amount;

                                // Reverse each related payment's balance impact
                                if ($related->type === 'expense' && $related->from_account_id) {
                                    Account::where('id', $related->from_account_id)->increment('balance', $relatedAmount);
                                } elseif ($related->type === 'income' && $related->to_account_id) {
                                    Account::where('id', $related->to_account_id)->decrement('balance', $relatedAmount);
                                }

                                $related->delete();
                            });

                        $debt->delete();
                    } else {
                        // This is a payment transaction → increase remaining_amount
                        $debt->remaining_amount = (float) $debt->remaining_amount + $amount;
                        $debt->recalculateStatus();
                    }
                }
            }

            // ── 3. Soft-delete the transaction ──────────────────────────
            $transaction->delete();
        });

        return back()->with('success', 'Transaction deleted and balance reversed.');
    }
}
