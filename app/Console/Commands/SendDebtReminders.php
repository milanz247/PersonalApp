<?php

namespace App\Console\Commands;

use App\Models\Debt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDebtReminders extends Command
{
    protected $signature = 'debts:send-reminders';
    protected $description = 'Send email reminders for lent debts approaching their due date';

    public function handle(): int
    {
        $count = 0;

        // Get all users who have debts with due dates
        $users = User::whereHas('debts', function ($q) {
            $q->where('type', 'lent')
              ->whereIn('status', ['pending', 'partially_paid'])
              ->whereNotNull('due_date')
              ->where(function ($q2) {
                  $q2->whereNotNull('contact_email')
                     ->orWhereNotNull('contact_phone');
              });
        })->get();

        foreach ($users as $user) {
            $tz        = $user->timezone ?? 'Asia/Colombo';
            $daysBefore= $user->debt_reminder_days_before ?? 2;
            $today     = Carbon::now($tz)->startOfDay();

            $debts = Debt::where('user_id', $user->id)
                ->where('type', 'lent')
                ->whereIn('status', ['pending', 'partially_paid'])
                ->whereNotNull('due_date')
                ->where(function ($q) {
                    $q->whereNotNull('contact_email')
                      ->orWhereNotNull('contact_phone');
                })
                ->get();

            foreach ($debts as $debt) {
                $dueDate  = $debt->due_date->startOfDay();
                $daysLeft = $today->diffInDays($dueDate, false);

                // Send reminder if due date is exactly X days away, or if overdue (daily)
                $shouldSend = false;

                if ($daysLeft === $daysBefore) {
                    $shouldSend = true;
                } elseif ($daysLeft <= 0) {
                    // Overdue — send daily but only once per day
                    $lastSent = $debt->last_reminder_sent_at;
                    if (! $lastSent || $lastSent->startOfDay()->lt($today)) {
                        $shouldSend = true;
                    }
                }

                if (! $shouldSend) {
                    continue;
                }

                $sent = $this->sendReminder($user, $debt);
                if ($sent) {
                    $count++;
                    $this->info("Reminder sent for debt #{$debt->id} ({$debt->person_name})");
                }
            }
        }

        $this->info("Sent {$count} reminder(s).");

        return self::SUCCESS;
    }

    private function sendReminder($user, Debt $debt): bool
    {
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
                Log::warning('Scheduled debt reminder email failed', ['debt_id' => $debt->id, 'error' => $e->getMessage()]);
            }
        }

        if ($debt->contact_phone) {
            $phone = preg_replace('/[^0-9]/', '', $debt->contact_phone);
            Log::info('Scheduled WhatsApp reminder', [
                'debt_id' => $debt->id,
                'phone'   => $phone,
                'link'    => "https://wa.me/{$phone}?text=" . urlencode($body),
            ]);
            $sent = true;
        }

        if ($sent) {
            $debt->update(['last_reminder_sent_at' => now()]);
        }

        return $sent;
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
}
