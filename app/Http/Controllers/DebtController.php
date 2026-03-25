<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\Debt;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DebtController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $debts = $user->debts()
            ->with('account:id,name,type')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalOwed       = $debts->where('type', 'borrowed')->whereIn('status', ['pending', 'partially_paid'])->sum('remaining_amount');
        $totalReceivable = $debts->where('type', 'lent')->whereIn('status', ['pending', 'partially_paid'])->sum('remaining_amount');

        return Inertia::render('Debts/Index', [
            'debts'            => $debts,
            'totalOwed'        => (float) $totalOwed,
            'totalReceivable'  => (float) $totalReceivable,
        ]);
    }

    /**
     * Record a new debt (borrowed or lent).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type'        => ['required', 'in:borrowed,lent'],
            'person_name' => ['required', 'string', 'max:255'],
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'account_id'  => ['required', 'integer', 'exists:accounts,id'],
            'fee'         => ['nullable', 'numeric', 'min:0'],
            'due_date'    => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $user      = $request->user();
        $amount    = (float) $validated['amount'];
        $fee       = (float) ($validated['fee'] ?? 0);
        $isBorrow  = $validated['type'] === 'borrowed';

        // Verify account ownership
        $account = Account::where('id', $validated['account_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        // For lending, ensure sufficient balance
        if (!$isBorrow) {
            $totalDeduct = $amount + $fee;
            if ((float) $account->balance < $totalDeduct) {
                return back()->withErrors(['amount' => "Insufficient balance. Available: {$account->balance}, Required: {$totalDeduct}"])->withInput();
            }
        }

        DB::transaction(function () use ($user, $validated, $account, $amount, $fee, $isBorrow) {
            // Look up system categories
            $bankFeesCategory = Category::where('name', 'Bank/ATM Fees')->whereNull('user_id')->first();
            $debtCategory     = Category::where('name', 'Loans & Debts')->whereNull('user_id')->first();

            // Create the debt record first so we can link transactions via debt_id
            $debt = Debt::create([
                'user_id'          => $user->id,
                'account_id'       => $account->id,
                'person_name'      => $validated['person_name'],
                'type'             => $validated['type'],
                'amount'           => $amount,
                'remaining_amount' => $amount,
                'due_date'         => $validated['due_date'] ?? null,
                'status'           => 'pending',
                'description'      => $validated['description'] ?? null,
            ]);

            if ($isBorrow) {
                // Borrowing: money comes INTO the account
                $account->increment('balance', $amount);

                Transaction::create([
                    'user_id'       => $user->id,
                    'to_account_id' => $account->id,
                    'category_id'   => $debtCategory?->id,
                    'debt_id'       => $debt->id,
                    'type'          => 'income',
                    'amount'        => $amount,
                    'fee'           => 0,
                    'date'          => now()->toDateString(),
                    'note'          => 'Borrowed from ' . $validated['person_name'],
                ]);
            } else {
                // Lending: amount + fee go OUT of the account
                $account->decrement('balance', $amount + $fee);

                // Main loan transaction
                Transaction::create([
                    'user_id'         => $user->id,
                    'from_account_id' => $account->id,
                    'category_id'     => $debtCategory?->id,
                    'debt_id'         => $debt->id,
                    'type'            => 'expense',
                    'amount'          => $amount,
                    'fee'             => 0,
                    'date'            => now()->toDateString(),
                    'note'            => 'Loan to ' . $validated['person_name'],
                ]);

                // Fee transaction (only if fee > 0)
                if ($fee > 0) {
                    Transaction::create([
                        'user_id'         => $user->id,
                        'from_account_id' => $account->id,
                        'category_id'     => $bankFeesCategory?->id,
                        'debt_id'         => $debt->id,
                        'type'            => 'expense',
                        'amount'          => $fee,
                        'fee'             => 0,
                        'date'            => now()->toDateString(),
                        'note'            => 'Fee for loan to ' . $validated['person_name'],
                    ]);
                }
            }
        });

        $label = $isBorrow ? 'Debt (borrowed)' : 'Loan (lent)';

        return back()->with('success', "{$label} recorded successfully.");
    }

    /**
     * Record a partial or full payment against a debt.
     */
    public function addPayment(Request $request, Debt $debt): RedirectResponse
    {
        // Ensure the debt belongs to the authenticated user
        abort_if($debt->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'payment_amount' => ['required', 'numeric', 'min:0.01'],
            'account_id'     => ['required', 'integer', 'exists:accounts,id'],
            'fee'            => ['nullable', 'numeric', 'min:0'],
        ]);

        $user      = $request->user();
        $payment   = (float) $validated['payment_amount'];
        $fee       = (float) ($validated['fee'] ?? 0);
        $remaining = (float) $debt->remaining_amount;

        if ($debt->status === 'settled') {
            return back()->with('error', 'This debt is already settled.');
        }

        if ($payment > $remaining) {
            return back()->withErrors(['payment_amount' => "Payment ({$payment}) exceeds remaining amount ({$remaining})."]);
        }

        $account = Account::where('id', $validated['account_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        // For borrowed debts: we are paying back → total deduction must not exceed balance
        if ($debt->type === 'borrowed') {
            $totalDeduct = $payment + $fee;
            if ((float) $account->balance < $totalDeduct) {
                return back()->withErrors(['payment_amount' => "Insufficient balance. Available: {$account->balance}, Required: {$totalDeduct} (payment + fee)."]);
            }
        }

        DB::transaction(function () use ($user, $debt, $account, $payment, $fee) {
            $debtCategory     = Category::where('name', 'Loans & Debts')->whereNull('user_id')->first();
            $bankFeesCategory = Category::where('name', 'Bank/ATM Fees')->whereNull('user_id')->first();

            if ($debt->type === 'borrowed') {
                // We are paying someone back → payment + fee leave our account
                $account->decrement('balance', $payment + $fee);

                // Main repayment transaction
                Transaction::create([
                    'user_id'         => $user->id,
                    'from_account_id' => $account->id,
                    'category_id'     => $debtCategory?->id,
                    'debt_id'         => $debt->id,
                    'type'            => 'expense',
                    'amount'          => $payment,
                    'fee'             => 0,
                    'date'            => now()->toDateString(),
                    'note'            => 'Debt repayment to ' . $debt->person_name,
                ]);

                // Separate fee transaction (only if fee > 0)
                if ($fee > 0) {
                    Transaction::create([
                        'user_id'         => $user->id,
                        'from_account_id' => $account->id,
                        'category_id'     => $bankFeesCategory?->id,
                        'debt_id'         => $debt->id,
                        'type'            => 'expense',
                        'amount'          => $fee,
                        'fee'             => 0,
                        'date'            => now()->toDateString(),
                        'note'            => 'Transaction fee for debt repayment to ' . $debt->person_name,
                    ]);
                }
            } else {
                // Someone is paying us back → money comes into our account (no fee deduction on received payment)
                $account->increment('balance', $payment);

                Transaction::create([
                    'user_id'       => $user->id,
                    'to_account_id' => $account->id,
                    'category_id'   => $debtCategory?->id,
                    'debt_id'       => $debt->id,
                    'type'          => 'income',
                    'amount'        => $payment,
                    'fee'           => 0,
                    'date'          => now()->toDateString(),
                    'note'          => 'Loan repayment from ' . $debt->person_name,
                ]);

                // Fee for receiving (e.g. bank charge for incoming transfer)
                if ($fee > 0) {
                    $account->decrement('balance', $fee);

                    Transaction::create([
                        'user_id'         => $user->id,
                        'from_account_id' => $account->id,
                        'category_id'     => $bankFeesCategory?->id,
                        'debt_id'         => $debt->id,
                        'type'            => 'expense',
                        'amount'          => $fee,
                        'fee'             => 0,
                        'date'            => now()->toDateString(),
                        'note'            => 'Transaction fee for loan repayment from ' . $debt->person_name,
                    ]);
                }
            }

            $debt->remaining_amount = (float) $debt->remaining_amount - $payment;
            $debt->recalculateStatus();
        });

        return back()->with('success', 'Payment recorded successfully.');
    }
}
