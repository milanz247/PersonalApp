<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Debt;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $now  = Carbon::now();

        // ── Total balance across all accounts ──────────────────────────
        $totalBalance = $user->accounts()->sum('balance');

        // ── Current-month income & expenses ────────────────────────────
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd   = $now->copy()->endOfMonth();

        $monthlyIncome = Transaction::where('user_id', $user->id)
            ->where('type', 'income')
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->sum('amount');

        $monthlyExpense = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->sum('amount');

        // ── Category breakdown (expenses this month) ──────────────────
        $categoryBreakdown = Transaction::where('transactions.user_id', $user->id)
            ->where('transactions.type', 'expense')
            ->whereBetween('transactions.date', [$monthStart, $monthEnd])
            ->whereNotNull('transactions.category_id')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->select(
                'categories.name',
                'categories.color',
                DB::raw('SUM(transactions.amount) as total')
            )
            ->groupBy('categories.id', 'categories.name', 'categories.color')
            ->orderByDesc('total')
            ->get();

        // ── Monthly trend (last 30 days) ──────────────────────────────
        $thirtyDaysAgo = $now->copy()->subDays(29)->startOfDay();

        $dailyTrend = Transaction::where('user_id', $user->id)
            ->whereIn('type', ['income', 'expense'])
            ->whereBetween('date', [$thirtyDaysAgo, $now])
            ->select(
                DB::raw('DATE(date) as day'),
                'type',
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('day', 'type')
            ->orderBy('day')
            ->get();

        // Build keyed arrays for the last 30 days
        $trendDates   = [];
        $trendIncome  = [];
        $trendExpense = [];

        for ($i = 0; $i < 30; $i++) {
            $d = $thirtyDaysAgo->copy()->addDays($i)->format('Y-m-d');
            $trendDates[]   = $d;
            $trendIncome[$d]  = 0;
            $trendExpense[$d] = 0;
        }

        foreach ($dailyTrend as $row) {
            if ($row->type === 'income') {
                $trendIncome[$row->day] = round((float) $row->total, 2);
            } else {
                $trendExpense[$row->day] = round((float) $row->total, 2);
            }
        }

        // ── Recent transactions ───────────────────────────────────────
        $recentTransactions = Transaction::where('user_id', $user->id)
            ->with(['category:id,name,color', 'fromAccount:id,name', 'toAccount:id,name'])
            ->latest('date')
            ->latest('id')
            ->take(5)
            ->get()
            ->map(fn ($t) => [
                'id'           => $t->id,
                'type'         => $t->type,
                'amount'       => $t->amount,
                'date'         => $t->date->format('Y-m-d'),
                'note'         => $t->note,
                'category'     => $t->category ? ['name' => $t->category->name, 'color' => $t->category->color] : null,
                'from_account' => $t->fromAccount?->name,
                'to_account'   => $t->toAccount?->name,
            ]);

        // ── Pending debts summary ─────────────────────────────────────
        $pendingDebts = Debt::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'partially_paid'])
            ->select('id', 'person_name', 'type', 'amount', 'remaining_amount', 'due_date', 'status')
            ->orderBy('due_date')
            ->take(5)
            ->get();

        $totalBorrowed = Debt::where('user_id', $user->id)
            ->where('type', 'borrowed')
            ->whereIn('status', ['pending', 'partially_paid'])
            ->sum('remaining_amount');

        $totalLent = Debt::where('user_id', $user->id)
            ->where('type', 'lent')
            ->whereIn('status', ['pending', 'partially_paid'])
            ->sum('remaining_amount');

        // ── Budget alerts (categories over limit) ─────────────────────
        $currentMonthYear = $now->format('Y-m');

        $budgetAlerts = Budget::where('budgets.user_id', $user->id)
            ->where('budgets.month_year', $currentMonthYear)
            ->join('categories', 'budgets.category_id', '=', 'categories.id')
            ->leftJoin('transactions', function ($join) use ($user, $monthStart, $monthEnd) {
                $join->on('budgets.category_id', '=', 'transactions.category_id')
                    ->where('transactions.user_id', '=', $user->id)
                    ->where('transactions.type', '=', 'expense')
                    ->whereBetween('transactions.date', [$monthStart, $monthEnd]);
            })
            ->groupBy('budgets.id', 'budgets.amount', 'categories.name', 'categories.color')
            ->select(
                'categories.name as category_name',
                'categories.color as category_color',
                'budgets.amount as budget_amount',
                DB::raw('COALESCE(SUM(transactions.amount), 0) as spent_amount')
            )
            ->havingRaw('spent_amount > budgets.amount')
            ->get();

        return Inertia::render('Dashboard', [
            'totalBalance'       => round((float) $totalBalance, 2),
            'monthlyIncome'      => round((float) $monthlyIncome, 2),
            'monthlyExpense'     => round((float) $monthlyExpense, 2),
            'categoryBreakdown'  => $categoryBreakdown,
            'trendDates'         => $trendDates,
            'trendIncome'        => array_values($trendIncome),
            'trendExpense'       => array_values($trendExpense),
            'recentTransactions' => $recentTransactions,
            'pendingDebts'       => $pendingDebts,
            'totalBorrowed'      => round((float) $totalBorrowed, 2),
            'totalLent'          => round((float) $totalLent, 2),
            'budgetAlerts'       => $budgetAlerts,
        ]);
    }
}
