<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\Debt;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
            'type'          => ['required', 'in:borrowed,lent'],
            'person_name'   => ['required', 'string', 'max:255'],
            'amount'        => ['required', 'numeric', 'min:0.01'],
            'account_id'    => ['required', 'integer', 'exists:accounts,id'],
            'fee'           => ['nullable', 'numeric', 'min:0'],
            'due_date'      => ['nullable', 'date'],
            'description'   => ['nullable', 'string', 'max:1000'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
        ]);

        $user      = $request->user();
        $amount    = (float) $validated['amount'];
        $fee       = (float) ($validated['fee'] ?? 0);
        $isBorrow  = $validated['type'] === 'borrowed';

        // Verify account ownership
        $account = Account::where('id', $validated['account_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        DB::transaction(function () use ($user, $validated, $account, $amount, $fee, $isBorrow) {
            // Look up system categories
            $bankFeesCategory = Category::where('name', 'Bank/ATM Fees')->whereNull('user_id')->first();
            $debtCategory     = Category::where('name', 'Loans & Debts')->whereNull('user_id')->first();

            // Re-fetch account with a row-level lock
            $account = Account::where('id', $account->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            // For lending, ensure sufficient balance
            if (!$isBorrow) {
                $totalDeduct = $amount + $fee;
                if ((float) $account->balance < $totalDeduct) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'amount' => 'Insufficient balance. Available: '
                            . number_format((float) $account->balance, 2)
                            . ', required: '
                            . number_format($totalDeduct, 2) . '.',
                    ]);
                }
            }

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
                'contact_email'    => $validated['contact_email'] ?? null,
                'contact_phone'    => $validated['contact_phone'] ?? null,
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

        // Send initial notification for lent debts (if auto-send enabled)
        $notifSent = false;
        if (! $isBorrow && $user->debt_auto_send_initial) {
            $debt = Debt::find($debt->id); // refresh
            if ($debt) {
                $notifSent = $this->sendInitialNotification($user, $debt);
            }
        }

        $msg = "{$label} recorded successfully.";
        if ($notifSent) {
            $msg .= ' Notification sent to ' . $validated['person_name'] . '.';
        }

        return back()->with('success', $msg);
    }

    /**
     * Manually send / resend initial notification for a lent debt.
     */
    public function sendNotification(Request $request, Debt $debt): JsonResponse
    {
        abort_if($debt->user_id !== $request->user()->id, 403);
        abort_if($debt->type !== 'lent', 422, 'Only lent debts can send notifications.');

        $sent = $this->sendInitialNotification($request->user(), $debt);

        return response()->json([
            'success' => $sent,
            'message' => $sent ? 'Notification sent successfully.' : 'No contact info available.',
        ]);
    }

    /**
     * Send a manual reminder for a lent debt.
     */
    public function sendReminderManual(Request $request, Debt $debt): JsonResponse
    {
        abort_if($debt->user_id !== $request->user()->id, 403);
        abort_if($debt->type !== 'lent', 422, 'Only lent debts can send reminders.');

        $sent = $this->sendReminder($request->user(), $debt);

        return response()->json([
            'success' => $sent,
            'message' => $sent ? 'Reminder sent successfully.' : 'No contact info available.',
        ]);
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

        DB::transaction(function () use ($user, $debt, $account, $payment, $fee) {
            $debtCategory     = Category::where('name', 'Loans & Debts')->whereNull('user_id')->first();
            $bankFeesCategory = Category::where('name', 'Bank/ATM Fees')->whereNull('user_id')->first();

            // Re-fetch account with a row-level lock
            $account = Account::where('id', $account->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($debt->type === 'borrowed') {
                // We are paying someone back → payment + fee leave our account
                $totalDeduct = $payment + $fee;
                if ((float) $account->balance < $totalDeduct) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'payment_amount' => 'Insufficient balance. Available: '
                            . number_format((float) $account->balance, 2)
                            . ', required: '
                            . number_format($totalDeduct, 2) . ' (payment + fee).',
                    ]);
                }
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

    // ── Debt Reminder Settings ───────────────────────────────────
    public function settings(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Debts/Settings', [
            'debtAutoSendInitial'   => (bool) $user->debt_auto_send_initial,
            'debtReminderDaysBefore'=> (int) ($user->debt_reminder_days_before ?? 2),
            'debtInitialMessage'    => $user->debt_initial_message ?? $this->defaultInitialMessage(),
            'debtReminderMessage'   => $user->debt_reminder_message ?? $this->defaultReminderMessage(),
        ]);
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'debt_auto_send_initial'    => ['required', 'boolean'],
            'debt_reminder_days_before' => ['required', 'integer', 'min:1', 'max:30'],
            'debt_initial_message'      => ['nullable', 'string', 'max:2000'],
            'debt_reminder_message'     => ['nullable', 'string', 'max:2000'],
        ]);

        $request->user()->update($validated);

        return back()->with('success', 'Debt notification settings saved.');
    }

    // ── Private helpers ──────────────────────────────────────────

    private function defaultInitialMessage(): string
    {
        return "Hi {person_name},\n\nThis is a friendly note to confirm that I've lent you Rs. {amount} on {date}."
            . "{due_date_line}\n\nPlease let me know if you have any questions.\n\nThank you!";
    }

    private function defaultReminderMessage(): string
    {
        return "Hi {person_name},\n\nThis is a gentle reminder that you have an outstanding balance of Rs. {remaining_amount} (originally Rs. {amount})."
            . "{due_date_line}\n\nPlease arrange payment at your earliest convenience.\n\nThank you!";
    }

    private function buildMessage(string $template, $user, Debt $debt): string
    {
        $tz = $user->timezone ?? 'Asia/Colombo';
        $dueDateLine = $debt->due_date
            ? "\nThe due date is " . $debt->due_date->format('F j, Y') . '.'
            : '';

        return str_replace(
            ['{person_name}', '{amount}', '{remaining_amount}', '{date}', '{due_date}', '{due_date_line}', '{lender_name}'],
            [
                $debt->person_name,
                number_format((float) $debt->amount, 2),
                number_format((float) $debt->remaining_amount, 2),
                $debt->created_at->timezone($tz)->format('F j, Y'),
                $debt->due_date ? $debt->due_date->format('F j, Y') : 'N/A',
                $dueDateLine,
                $user->name,
            ],
            $template
        );
    }

    private function sendInitialNotification($user, Debt $debt): bool
    {
        if (! $debt->contact_email && ! $debt->contact_phone) {
            return false;
        }

        $template = $user->debt_initial_message ?? $this->defaultInitialMessage();
        $body     = $this->buildMessage($template, $user, $debt);
        $sent     = false;

        // Send email
        if ($debt->contact_email) {
            try {
                Mail::raw($body, function ($message) use ($debt, $user) {
                    $message->to($debt->contact_email)
                            ->subject('Loan Confirmation — Rs. ' . number_format((float) $debt->amount, 2))
                            ->replyTo($user->email, $user->name);
                });
                $sent = true;
            } catch (\Throwable $e) {
                Log::warning('Debt initial email failed', ['debt_id' => $debt->id, 'error' => $e->getMessage()]);
            }
        }

        // Send WhatsApp via wa.me link (generate link, log it — actual sending requires Twilio/WhatsApp Business API)
        if ($debt->contact_phone) {
            $waMessage = urlencode($body);
            $phone     = preg_replace('/[^0-9+]/', '', $debt->contact_phone);
            $waLink    = "https://wa.me/{$phone}?text={$waMessage}";
            Log::info('WhatsApp notification link generated', ['debt_id' => $debt->id, 'link' => $waLink]);
            // Store the link for the frontend to open
            $debt->update(['initial_notification_sent' => true, 'last_reminder_sent_at' => now()]);
            $sent = true;
        }

        if ($sent) {
            $debt->update(['initial_notification_sent' => true, 'last_reminder_sent_at' => now()]);
        }

        return $sent;
    }

    private function sendReminder($user, Debt $debt): bool
    {
        if (! $debt->contact_email && ! $debt->contact_phone) {
            return false;
        }

        $template = $user->debt_reminder_message ?? $this->defaultReminderMessage();
        $body     = $this->buildMessage($template, $user, $debt);
        $sent     = false;

        if ($debt->contact_email) {
            try {
                Mail::raw($body, function ($message) use ($debt, $user) {
                    $message->to($debt->contact_email)
                            ->subject('Payment Reminder — Rs. ' . number_format((float) $debt->remaining_amount, 2))
                            ->replyTo($user->email, $user->name);
                });
                $sent = true;
            } catch (\Throwable $e) {
                Log::warning('Debt reminder email failed', ['debt_id' => $debt->id, 'error' => $e->getMessage()]);
            }
        }

        if ($debt->contact_phone) {
            $waMessage = urlencode($body);
            $phone     = preg_replace('/[^0-9+]/', '', $debt->contact_phone);
            $waLink    = "https://wa.me/{$phone}?text={$waMessage}";
            Log::info('WhatsApp reminder link generated', ['debt_id' => $debt->id, 'link' => $waLink]);
            $sent = true;
        }

        if ($sent) {
            $debt->update(['last_reminder_sent_at' => now()]);
        }

        return $sent;
    }

    /**
     * Generate WhatsApp link for a debt message.
     */
    public function whatsappLink(Request $request, Debt $debt): JsonResponse
    {
        abort_if($debt->user_id !== $request->user()->id, 403);

        $type = $request->query('type', 'reminder');
        $user = $request->user();

        $template = $type === 'initial'
            ? ($user->debt_initial_message ?? $this->defaultInitialMessage())
            : ($user->debt_reminder_message ?? $this->defaultReminderMessage());

        $body  = $this->buildMessage($template, $user, $debt);
        $phone = preg_replace('/[^0-9]/', '', $debt->contact_phone ?? '');

        return response()->json([
            'link'    => $phone ? "https://wa.me/{$phone}?text=" . urlencode($body) : null,
            'message' => $body,
        ]);
    }
}
