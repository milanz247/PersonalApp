<?php

namespace App\Http\Controllers;

use App\Models\Account;
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
            Transaction::create([
                'user_id'         => $user->id,
                'from_account_id' => $validated['from_account_id'] ?? null,
                'to_account_id'   => $validated['to_account_id'] ?? null,
                'category_id'     => $validated['category_id'],
                'type'            => $validated['type'],
                'amount'          => $amount,
                'fee'             => 0,
                'date'            => $validated['date'],
                'note'            => $validated['note'] ?? null,
            ]);

            if ($validated['type'] === 'expense') {
                $account = Account::where('id', $validated['from_account_id'])
                    ->where('user_id', $user->id)
                    ->firstOrFail();
                $account->decrement('balance', $amount);
            } else {
                $account = Account::where('id', $validated['to_account_id'])
                    ->where('user_id', $user->id)
                    ->firstOrFail();
                $account->increment('balance', $amount);
            }
        });

        $label = $validated['type'] === 'expense' ? 'Expense' : 'Income';

        return back()->with('success', "{$label} added successfully.");
    }
}
