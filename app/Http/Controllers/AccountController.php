<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    /**
     * Display the accounts index page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $wallet = $user->wallet;

        $bankAccounts = $user->accounts()
            ->where('type', 'bank')
            ->latest()
            ->get();

        // All accounts passed so the Transfer modal can populate from/to selects
        $allAccounts = $user->accounts()->select('id', 'name', 'type', 'balance')->get();

        return Inertia::render('Accounts/Index', [
            'wallet'       => $wallet,
            'bankAccounts' => $bankAccounts,
            'allAccounts'  => $allAccounts,
        ]);
    }

    /**
     * Store a new bank account for the authenticated user.
     * Only 'bank' type accounts may be created through this endpoint.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'bank_name'      => ['required', 'string', 'max:255'],
            'branch_name'    => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:255'],
            'balance'        => ['required', 'numeric', 'min:0'],
        ]);

        $request->user()->accounts()->create([
            'name'           => $validated['name'],
            'type'           => 'bank',
            'bank_name'      => $validated['bank_name'],
            'branch_name'    => $validated['branch_name'],
            'account_number' => $validated['account_number'],
            'balance'        => $validated['balance'],
        ]);

        return redirect()->route('accounts.index')
            ->with('success', 'Bank account added successfully.');
    }

    /**
     * Update the specified bank account (name and bank details only).
     * Balance is managed via transactions and transfers, not direct edits.
     */
    public function update(Request $request, Account $account): RedirectResponse
    {
        if ($account->type === 'wallet') {
            abort(403, 'The main wallet cannot be modified through this endpoint.');
        }

        if ($account->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'bank_name'      => ['required', 'string', 'max:255'],
            'branch_name'    => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:255'],
        ]);

        $account->update($validated);

        return redirect()->route('accounts.index')
            ->with('success', 'Bank account updated successfully.');
    }

    /**
     * Delete a bank account.
     * Blocks deletion of the wallet and accounts with existing transactions.
     */
    public function destroy(Request $request, Account $account): RedirectResponse
    {
        if ($account->type === 'wallet') {
            abort(403, 'The main wallet cannot be deleted.');
        }

        if ($account->user_id !== $request->user()->id) {
            abort(403);
        }

        $hasTransactions = Transaction::where('from_account_id', $account->id)
            ->orWhere('to_account_id', $account->id)
            ->exists();

        if ($hasTransactions) {
            return back()->withErrors([
                'account' => 'This account has existing transactions and cannot be deleted.',
            ]);
        }

        $account->delete();

        return redirect()->route('accounts.index')
            ->with('success', 'Bank account deleted successfully.');
    }
}
