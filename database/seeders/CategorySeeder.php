<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * System categories required for automated transactions.
     * Use firstOrCreate so this seeder is safely re-runnable.
     */
    public function run(): void
    {
        // System default categories (user_id = null → shared across all users)
        $system = [
            ['name' => 'Bank Transfer',  'type' => 'transfer', 'is_system' => true],
            ['name' => 'Bank/ATM Fees',  'type' => 'expense',  'is_system' => true],
            ['name' => 'Adjustment',     'type' => 'expense',  'is_system' => true],
            ['name' => 'Loans & Debts',  'type' => 'expense',  'is_system' => true],
        ];

        // Regular starter categories
        $regular = [
            ['name' => 'Food & Drinks', 'type' => 'expense',  'is_system' => false],
            ['name' => 'Transport',     'type' => 'expense',  'is_system' => false],
            ['name' => 'Utilities',     'type' => 'expense',  'is_system' => false],
            ['name' => 'Salary',        'type' => 'income',   'is_system' => false],
            ['name' => 'Freelance',     'type' => 'income',   'is_system' => false],
        ];

        foreach (array_merge($system, $regular) as $cat) {
            Category::firstOrCreate(
                ['name' => $cat['name'], 'user_id' => null],
                ['type' => $cat['type'], 'is_system' => $cat['is_system']],
            );
        }
    }
}
