<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Debt extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'account_id',
        'person_name',
        'type',
        'amount',
        'remaining_amount',
        'due_date',
        'status',
        'description',
        'contact_email',
        'contact_phone',
        'initial_notification_sent',
        'last_reminder_sent_at',
    ];

    protected $casts = [
        'amount'                     => 'decimal:2',
        'remaining_amount'           => 'decimal:2',
        'due_date'                   => 'date',
        'initial_notification_sent'  => 'boolean',
        'last_reminder_sent_at'      => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Recalculate status based on remaining_amount and persist.
     */
    public function recalculateStatus(): void
    {
        if ((float) $this->remaining_amount <= 0) {
            $this->status           = 'settled';
            $this->remaining_amount = 0;
        } elseif ((float) $this->remaining_amount < (float) $this->amount) {
            $this->status = 'partially_paid';
        } else {
            $this->status = 'pending';
        }

        $this->save();
    }
}
