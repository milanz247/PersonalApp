<?php

namespace App\Http\Middleware;

use App\Models\Account;
use App\Models\Category;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return array_merge(parent::share($request), [
            ...parent::share($request),
            'name'  => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth'  => [
                'user' => $request->user(),
            ],
            'flash' => [
                'success'       => $request->session()->get('success'),
                'error'         => $request->session()->get('error'),
                'telegram_sent' => $request->session()->get('telegram_sent'),
            ],
            'userSettings' => fn () => $request->user() ? [
                'currency_symbol' => $request->user()->currency_symbol ?? 'Rs.',
                'currency_code'   => $request->user()->currency_code   ?? 'LKR',
                'timezone'        => $request->user()->timezone        ?? 'Asia/Colombo',
                'date_format'     => $request->user()->date_format     ?? 'DD/MM/YYYY',
                'avatar_url'      => $request->user()->avatar
                    ? Storage::disk('public')->url($request->user()->avatar)
                    : null,
            ] : null,
            'expenseCategories' => fn () => $request->user()
                ? Category::where('type', 'expense')
                    ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $request->user()->id))
                    ->select('id', 'name', 'color')
                    ->orderByDesc('is_system')
                    ->orderBy('name')
                    ->get()
                : [],
            'incomeCategories' => fn () => $request->user()
                ? Category::where('type', 'income')
                    ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $request->user()->id))
                    ->select('id', 'name', 'color')
                    ->orderByDesc('is_system')
                    ->orderBy('name')
                    ->get()
                : [],
            'userAccounts' => fn () => $request->user()
                ? Account::where('user_id', $request->user()->id)
                    ->select('id', 'name', 'type', 'balance')
                    ->get()
                : [],
        ]);
    }
}

