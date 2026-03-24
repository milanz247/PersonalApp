<?php

namespace App\Http\Controllers;

use App\Models\Account;
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
}
