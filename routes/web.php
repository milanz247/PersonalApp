<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\RecurringTransactionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransferController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('accounts', [AccountController::class, 'index'])->name('accounts.index');
    Route::post('accounts', [AccountController::class, 'store'])->name('accounts.store');
    Route::post('transfers', [TransferController::class, 'store'])->name('transfers.store');
    Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::delete('transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('debts', [DebtController::class, 'index'])->name('debts.index');
    Route::post('debts', [DebtController::class, 'store'])->name('debts.store');
    Route::post('debts/{debt}/payment', [DebtController::class, 'addPayment'])->name('debts.payment');

    // Budgets
    Route::get('budgets', [BudgetController::class, 'index'])->name('budgets.index');
    Route::post('budgets', [BudgetController::class, 'store'])->name('budgets.store');

    // Recurring Transactions
    Route::get('recurring', [RecurringTransactionController::class, 'index'])->name('recurring.index');
    Route::post('recurring', [RecurringTransactionController::class, 'store'])->name('recurring.store');
    Route::post('recurring/{recurringTransaction}/toggle', [RecurringTransactionController::class, 'toggleStatus'])->name('recurring.toggle');
    Route::post('recurring/{recurringTransaction}/run', [RecurringTransactionController::class, 'runNow'])->name('recurring.run');
    Route::delete('recurring/{recurringTransaction}', [RecurringTransactionController::class, 'destroy'])->name('recurring.destroy');

    // Reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/pdf', [ReportController::class, 'generateMonthlyReport'])->name('reports.pdf');
    Route::get('reports/csv', [ReportController::class, 'exportCsv'])->name('reports.csv');

    // Backup
    Route::post('backup/run', [BackupController::class, 'runBackup'])->name('backup.run');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
