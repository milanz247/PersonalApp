<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Monthly Financial Report - {{ $period }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; padding: 30px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #3b82f6; padding-bottom: 15px; }
        .header h1 { font-size: 22px; color: #1e40af; margin-bottom: 4px; }
        .header p { font-size: 13px; color: #6b7280; }
        .section { margin-bottom: 25px; }
        .section-title { font-size: 15px; font-weight: bold; color: #1e40af; margin-bottom: 10px; border-bottom: 1px solid #e5e7eb; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background-color: #eff6ff; color: #1e40af; text-align: left; padding: 8px 10px; font-size: 11px; border-bottom: 2px solid #bfdbfe; }
        td { padding: 7px 10px; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
        tr:nth-child(even) td { background-color: #f9fafb; }
        .summary-grid { display: table; width: 100%; margin-bottom: 10px; }
        .summary-row { display: table-row; }
        .summary-cell { display: table-cell; width: 25%; padding: 12px; text-align: center; }
        .summary-cell .label { font-size: 10px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
        .summary-cell .value { font-size: 18px; font-weight: bold; margin-top: 4px; }
        .text-green { color: #16a34a; }
        .text-red { color: #dc2626; }
        .text-blue { color: #2563eb; }
        .color-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 6px; vertical-align: middle; }
        .text-right { text-align: right; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Monthly Financial Report</h1>
        <p>{{ $userName }} &mdash; {{ $period }}</p>
    </div>

    {{-- Summary --}}
    <div class="section">
        <div class="section-title">Summary</div>
        <table>
            <tr>
                <td><strong>Total Income</strong></td>
                <td class="text-right text-green"><strong>{{ $currencySymbol }} {{ number_format($totalIncome, 2) }}</strong></td>
                <td><strong>Total Expense</strong></td>
                <td class="text-right text-red"><strong>{{ $currencySymbol }} {{ number_format($totalExpense, 2) }}</strong></td>
            </tr>
            <tr>
                <td><strong>Net Savings</strong></td>
                <td class="text-right {{ $savings >= 0 ? 'text-green' : 'text-red' }}"><strong>{{ $currencySymbol }} {{ number_format($savings, 2) }}</strong></td>
                <td><strong>Savings Rate</strong></td>
                <td class="text-right text-blue"><strong>{{ $savingsRate }}%</strong></td>
            </tr>
        </table>
    </div>

    {{-- Expenses by Category --}}
    <div class="section">
        <div class="section-title">Expenses by Category</div>
        @if($categoryBreakdown->count())
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th class="text-right">Amount</th>
                        <th class="text-right">% of Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categoryBreakdown as $cat)
                        <tr>
                            <td>
                                <span class="color-dot" style="background-color: {{ $cat->color ?? '#6b7280' }};"></span>
                                {{ $cat->name }}
                            </td>
                            <td class="text-right">{{ $currencySymbol }} {{ number_format($cat->total, 2) }}</td>
                            <td class="text-right">{{ $totalExpense > 0 ? number_format(($cat->total / $totalExpense) * 100, 1) : '0' }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="color: #9ca3af; padding: 10px 0;">No expenses recorded for this period.</p>
        @endif
    </div>

    {{-- Account Balances --}}
    <div class="section">
        <div class="section-title">Account Balances</div>
        <table>
            <thead>
                <tr>
                    <th>Account</th>
                    <th>Type</th>
                    <th class="text-right">Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($accountBalances as $acc)
                    <tr>
                        <td>{{ $acc->name }}</td>
                        <td>{{ ucfirst($acc->type) }}</td>
                        <td class="text-right">{{ $currencySymbol }} {{ number_format($acc->balance, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        Generated on {{ now()->format('F d, Y \a\t h:i A') }} &mdash; Personal Finance Tracker
    </div>
</body>
</html>
