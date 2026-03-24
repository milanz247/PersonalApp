<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'account_id',
        'category_id',
        'amount',
        'description',
        'type',
        'frequency',
        'start_date',
        'last_executed_at',
        'next_date',
        'status',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'start_date'       => 'date',
        'next_date'        => 'date',
        'last_executed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Calculate the next execution date based on the frequency.
     */
    public function calculateNextDate(): Carbon
    {
        $current = $this->next_date->copy();

        return match ($this->frequency) {
            'daily'   => $current->addDay(),
            'weekly'  => $current->addWeek(),
            'monthly' => $current->addMonth(),
            'yearly'  => $current->addYear(),
        };
    }
}
