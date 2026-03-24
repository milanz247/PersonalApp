<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Reports/Index');
    }

    /**
     * Generate a monthly PDF report.
     */
    public function generateMonthlyReport(Request $request)
    {
        $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        $user       = $request->user();
        $monthYear  = $request->input('month');
        $monthStart = Carbon::createFromFormat('Y-m', $monthYear)->startOfMonth();
        $monthEnd   = $monthStart->copy()->endOfMonth();

        $totalIncome = Transaction::where('user_id', $user->id)
            ->where('type', 'income')
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->sum('amount');

        $totalExpense = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->sum('amount');

        $savings     = (float) $totalIncome - (float) $totalExpense;
        $savingsRate = (float) $totalIncome > 0
            ? round($savings / (float) $totalIncome * 100, 1)
            : 0;

        $categoryBreakdown = Transaction::where('transactions.user_id', $user->id)
            ->where('transactions.type', 'expense')
            ->whereBetween('transactions.date', [$monthStart, $monthEnd])
            ->whereNotNull('transactions.category_id')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->select('categories.name', 'categories.color', DB::raw('SUM(transactions.amount) as total'))
            ->groupBy('categories.id', 'categories.name', 'categories.color')
            ->orderByDesc('total')
            ->get();

        $accountBalances = $user->accounts()->select('name', 'type', 'balance')->get();

        $data = [
            'userName'          => $user->name,
            'period'            => $monthStart->format('F Y'),
            'currencySymbol'    => $user->currency_symbol ?? 'Rs.',
            'totalIncome'       => round((float) $totalIncome, 2),
            'totalExpense'      => round((float) $totalExpense, 2),
            'savings'           => round($savings, 2),
            'savingsRate'       => $savingsRate,
            'categoryBreakdown' => $categoryBreakdown,
            'accountBalances'   => $accountBalances,
        ];

        $pdf = Pdf::loadView('reports.monthly_pdf', $data);

        return $pdf->download("financial-report-{$monthYear}.pdf");
    }

    /**
     * Export transactions as CSV for a date range.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $request->validate([
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $user      = $request->user();
        $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
        $endDate   = Carbon::parse($request->input('end_date'))->endOfDay();

        $transactions = Transaction::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->with(['category:id,name', 'fromAccount:id,name', 'toAccount:id,name'])
            ->orderBy('date', 'desc')
            ->get();

        $filename = "transactions-{$startDate->format('Y-m-d')}-to-{$endDate->format('Y-m-d')}.csv";

        return response()->streamDownload(function () use ($transactions) {
            $handle = fopen('php://output', 'w');

            // Header row
            fputcsv($handle, ['Date', 'Type', 'Category', 'Amount', 'Fee', 'From Account', 'To Account', 'Note']);

            foreach ($transactions as $tx) {
                fputcsv($handle, [
                    $tx->date->format('Y-m-d'),
                    ucfirst($tx->type),
                    $tx->category?->name ?? '-',
                    number_format((float) $tx->amount, 2, '.', ''),
                    number_format((float) $tx->fee, 2, '.', ''),
                    $tx->fromAccount?->name ?? '-',
                    $tx->toAccount?->name ?? '-',
                    $tx->note ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
