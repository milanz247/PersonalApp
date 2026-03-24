<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function index(Request $request): Response
    {
        $user      = $request->user();
        $monthYear = $request->query('month', Carbon::now()->format('Y-m'));

        $monthStart = Carbon::createFromFormat('Y-m', $monthYear)->startOfMonth();
        $monthEnd   = $monthStart->copy()->endOfMonth();

        // Get all expense categories (system + user-created)
        $categories = DB::table('categories')
            ->where('type', 'expense')
            ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $user->id))
            ->select('id', 'name', 'color')
            ->orderBy('name')
            ->get();

        // Get budgets for this month
        $budgets = Budget::where('user_id', $user->id)
            ->where('month_year', $monthYear)
            ->pluck('amount', 'category_id');

        // Get actual spending per category for this month
        $spending = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereNotNull('category_id')
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->groupBy('category_id')
            ->select('category_id', DB::raw('SUM(amount) as total'))
            ->pluck('total', 'category_id');

        // Merge into a list
        $budgetData = $categories->map(fn ($cat) => [
            'category_id'   => $cat->id,
            'category_name' => $cat->name,
            'category_color' => $cat->color,
            'budget_amount' => (float) ($budgets[$cat->id] ?? 0),
            'spent_amount'  => round((float) ($spending[$cat->id] ?? 0), 2),
        ]);

        return Inertia::render('Budgets/Index', [
            'budgetData'   => $budgetData,
            'currentMonth' => $monthYear,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'amount'      => ['required', 'numeric', 'min:0'],
            'month_year'  => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        Budget::updateOrCreate(
            [
                'user_id'     => $request->user()->id,
                'category_id' => $validated['category_id'],
                'month_year'  => $validated['month_year'],
            ],
            [
                'amount' => $validated['amount'],
            ],
        );

        return back()->with('success', 'Budget updated successfully.');
    }
}
