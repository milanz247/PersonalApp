<?php

namespace App\Observers;

use App\Models\Account;
use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     * Automatically creates a Main Wallet account for every new user.
     */
    public function created(User $user): void
    {
        // Guard: only create if no wallet exists yet (idempotent)
        if (! $user->accounts()->where('type', 'wallet')->exists()) {
            Account::create([
                'user_id' => $user->id,
                'name'    => 'Main Wallet',
                'type'    => 'wallet',
                'balance' => 0.00,
            ]);
        }
    }
}
