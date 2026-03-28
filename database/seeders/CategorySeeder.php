<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Seeds global (user_id = null) categories.
     * System categories required by automated transactions are kept separate
     * so they are never accidentally omitted. Safely re-runnable via updateOrCreate.
     */
    public function run(): void
    {
        // ─── System categories (used by controller logic — do NOT remove) ──────
        $system = [
            ['name' => 'Bank Transfer', 'type' => 'transfer', 'icon' => 'Repeat',    'color' => '#94a3b8', 'is_system' => true],
            ['name' => 'Bank/ATM Fees', 'type' => 'expense',  'icon' => 'Info',      'color' => '#64748b', 'is_system' => true],
            ['name' => 'Adjustment',    'type' => 'expense',  'icon' => 'Settings',  'color' => '#94a3b8', 'is_system' => true],
            ['name' => 'Loans & Debts', 'type' => 'expense',  'icon' => 'Landmark',  'color' => '#b91c1c', 'is_system' => true],
            ['name' => 'Internal Transfer', 'type' => 'transfer', 'icon' => 'Repeat', 'color' => '#94a3b8', 'is_system' => true],
        ];

        // ─── Income categories ────────────────────────────────────────────────
        $income = [
            ['name' => 'Salary',          'type' => 'income', 'icon' => 'Banknote', 'color' => '#10b981'],
            ['name' => 'Freelance/Other', 'type' => 'income', 'icon' => 'Laptop',   'color' => '#3b82f6'],
        ];

        // ─── Expense categories ───────────────────────────────────────────────
        $expense = [
            // Housing & Bills
            ['name' => 'Boarding Rent',              'type' => 'expense', 'icon' => 'Home',         'color' => '#ef4444'],
            ['name' => 'Utility Bills (Elec/Water)', 'type' => 'expense', 'icon' => 'Zap',          'color' => '#f59e0b'],
            ['name' => 'Mobile & Data',              'type' => 'expense', 'icon' => 'Smartphone',   'color' => '#6366f1'],

            // Food & Drinks
            ['name' => 'Daily Meals (Ude/Dawal/Ra)', 'type' => 'expense', 'icon' => 'Utensils',    'color' => '#f97316'],
            ['name' => 'Tea & Snacks (Ice Cream)',   'type' => 'expense', 'icon' => 'Coffee',       'color' => '#fbbf24'],
            ['name' => 'Cigarettes',                 'type' => 'expense', 'icon' => 'Smoking',      'color' => '#475569'],

            // Shopping & Necessities
            ['name' => 'Groceries (Kaden ganna badu)', 'type' => 'expense', 'icon' => 'ShoppingCart', 'color' => '#84cc16'],
            ['name' => 'General Shopping (Clothes)',   'type' => 'expense', 'icon' => 'ShoppingBag',  'color' => '#ec4899'],

            // Travel & Leisure
            ['name' => 'Transport (Bus/PickMe)', 'type' => 'expense', 'icon' => 'Bus', 'color' => '#06b6d4'],
            ['name' => 'Hotel Stays (Travel)',   'type' => 'expense', 'icon' => 'Bed', 'color' => '#8b5cf6'],

            // Social & Charity
            ['name' => 'Charity (Pinta dunna)',      'type' => 'expense', 'icon' => 'Heart', 'color' => '#f43f5e'],
            ['name' => 'Treats for Friends (Mama gaane)', 'type' => 'expense', 'icon' => 'Users', 'color' => '#d946ef'],

            // Financial
            ['name' => 'Loan Repayments', 'type' => 'expense', 'icon' => 'Landmark', 'color' => '#b91c1c'],
        ];

        foreach (array_merge($system, $income, $expense) as $cat) {
            Category::updateOrCreate(
                ['name' => $cat['name'], 'user_id' => null],
                [
                    'type'      => $cat['type'],
                    'icon'      => $cat['icon'],
                    'color'     => $cat['color'],
                    'is_system' => $cat['is_system'] ?? false,
                ],
            );
        }
    }
}
