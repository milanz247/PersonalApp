<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class NoteController extends Controller
{
    // ── Dashboard ────────────────────────────────────────────────
    public function index(Request $request): Response
    {
        $user = $request->user();
        $tz   = $user->timezone ?? 'Asia/Colombo';
        $now  = Carbon::now($tz);

        $allNotes = Note::where('user_id', $user->id)->whereNotNull('note_date')->get();

        $totalEntries     = $allNotes->count();
        $thisMonthEntries = $allNotes
            ->filter(fn ($n) => $n->note_date->year === $now->year && $n->note_date->month === $now->month)
            ->count();

        $grouped      = $allNotes->groupBy(fn ($n) => $n->note_date->format('Y-m'))->map->count()->sortDesc();
        $bestMonthKey = $grouped->keys()->first();
        $bestMonthCnt = $grouped->first();
        $bestMonth    = $bestMonthKey
            ? date('F Y', strtotime($bestMonthKey . '-01')) . " ({$bestMonthCnt})"
            : '-';

        $dates = $allNotes->pluck('note_date')->map(fn ($d) => $d->format('Y-m-d'))->unique()->sort()->values();
        $streak = 0;
        $check  = $now->copy()->startOfDay();
        while ($dates->contains($check->format('Y-m-d'))) {
            $streak++;
            $check->subDay();
        }

        $stats = [
            'totalEntries'    => $totalEntries,
            'thisMonthEntries'=> $thisMonthEntries,
            'bestMonth'       => $bestMonth,
            'streak'          => $streak,
        ];

        $recentEntries = Note::where('user_id', $user->id)
            ->whereNotNull('note_date')
            ->orderByDesc('note_date')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $onThisDay = Note::where('user_id', $user->id)
            ->whereDate('note_date', $now->copy()->subYear()->toDateString())
            ->orderBy('created_at')
            ->get();

        return Inertia::render('Notes/Index', [
            'stats'         => $stats,
            'recentEntries' => $recentEntries,
            'onThisDay'     => $onThisDay,
        ]);
    }

    // ── Planner (Year calendar) ─────────────────────────────────
    public function planner(Request $request): Response
    {
        $user = $request->user();
        $tz   = $user->timezone ?? 'Asia/Colombo';
        $year = (int) $request->query('year', Carbon::now($tz)->year);

        $yearCounts = Note::where('user_id', $user->id)
            ->whereNotNull('note_date')
            ->whereBetween('note_date', ["{$year}-01-01", "{$year}-12-31"])
            ->selectRaw('DATE(note_date) as d, COUNT(*) as cnt')
            ->groupBy('d')
            ->pluck('cnt', 'd');

        return Inertia::render('Notes/Planner', [
            'year'        => $year,
            'yearCounts'  => $yearCounts,
            'todayStr'    => Carbon::now($tz)->toDateString(),
            'selectedDay' => $request->query('day'),
        ]);
    }

    // ── New Entry page ──────────────────────────────────────────
    public function newEntry(Request $request): Response
    {
        $user = $request->user();
        $tz   = $user->timezone ?? 'Asia/Colombo';
        return Inertia::render('Notes/NewEntry', [
            'date'          => $request->query('date', Carbon::now($tz)->toDateString()),
            'hasTelegram'   => (bool) $user->telegram_bot_token && (bool) $user->telegram_chat_id,
            'userTimezone'  => $tz,
        ]);
    }

    // ── Diary Settings page ─────────────────────────────────────
    public function diarySettings(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Notes/DiarySettings', [
            'telegramBotToken' => $user->telegram_bot_token ? '••••••' . substr($user->telegram_bot_token, -6) : '',
            'telegramChatId'   => $user->telegram_chat_id ?? '',
            'hasTelegram'      => (bool) $user->telegram_bot_token && (bool) $user->telegram_chat_id,            'hasWebhook'       => (bool) $user->telegram_webhook_secret,        ]);
    }

    public function saveDiarySettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'telegram_bot_token' => ['nullable', 'string', 'max:255'],
            'telegram_chat_id'   => ['nullable', 'string', 'max:100'],
        ]);

        $user = $request->user();

        if ($validated['telegram_bot_token'] && ! str_starts_with($validated['telegram_bot_token'], '••')) {
            $user->telegram_bot_token = $validated['telegram_bot_token'];
        }

        if (array_key_exists('telegram_chat_id', $validated)) {
            $user->telegram_chat_id = $validated['telegram_chat_id'];
        }

        $user->save();

        return back()->with('success', 'Diary settings saved.');
    }

    // ── Day notes JSON (for planner day-dialog) ──────────────────
    public function dayNotesJson(Request $request): JsonResponse
    {
        $date = (string) $request->query('date', now()->toDateString());

        $notes = Note::where('user_id', $request->user()->id)
            ->whereNotNull('note_date')
            ->whereDate('note_date', $date)
            ->orderBy('created_at')
            ->get();

        return response()->json(['notes' => $notes]);
    }

    // ── Search ──────────────────────────────────────────────────
    public function search(Request $request): JsonResponse
    {
        $q = (string) $request->query('q', '');

        $results = [];
        if ($q !== '') {
            $notes = Note::where('user_id', $request->user()->id)
                ->whereNotNull('note_date')
                ->where(function ($query) use ($q) {
                    $query->where('title', 'like', "%{$q}%")
                          ->orWhere('content', 'like', "%{$q}%");
                })
                ->orderBy('note_date', 'desc')
                ->get();

            $results = $notes->groupBy(fn ($n) => $n->note_date->format('Y-m-d'))
                ->map(fn ($group, $date) => ['date' => $date, 'notes' => $group->values()])
                ->values();
        }

        return response()->json(['results' => $results]);
    }

    // ── Store ────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'title'     => ['nullable', 'string', 'max:255'],
            'content'   => ['nullable', 'string'],
            'color'     => ['required', 'in:default,yellow,green,blue,pink,purple'],
            'note_date' => ['required', 'date'],
            'mood'      => ['nullable', 'in:happy,sad,angry,calm,excited'],
        ]);

        // Auto-generate title from date if not provided
        if (empty($validated['title'])) {
            $tz = $request->user()->timezone ?? 'Asia/Colombo';
            $validated['title'] = Carbon::parse($validated['note_date'], $tz)
                ->format('l, F j, Y');
        }

        $note = $request->user()->notes()->create($validated);

        // Send to Telegram if configured
        $telegramSent = $this->sendToTelegram($request->user(), $note);

        if ($request->wantsJson()) {
            return response()->json(['note' => $note->fresh(), 'success' => true, 'telegram_sent' => $telegramSent]);
        }

        $response = back()->with('success', 'Entry saved.');
        if ($telegramSent !== null) {
            $response = $response->with('telegram_sent', $telegramSent);
        }

        return $response;
    }

    // ── Update ───────────────────────────────────────────────────
    public function update(Request $request, Note $note): JsonResponse|RedirectResponse
    {
        abort_if($note->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'title'     => ['required', 'string', 'max:255'],
            'content'   => ['nullable', 'string'],
            'color'     => ['required', 'in:default,yellow,green,blue,pink,purple'],
            'note_date' => ['required', 'date'],
            'mood'      => ['nullable', 'in:happy,sad,angry,calm,excited'],
        ]);

        $note->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Entry updated.');
    }

    // ── Toggle Pin ───────────────────────────────────────────────
    public function togglePin(Request $request, Note $note): RedirectResponse
    {
        abort_if($note->user_id !== $request->user()->id, 403);

        $note->update(['is_pinned' => ! $note->is_pinned]);

        return back()->with('success', $note->is_pinned ? 'Note pinned.' : 'Note unpinned.');
    }

    // ── Destroy ──────────────────────────────────────────────────
    public function destroy(Request $request, Note $note): RedirectResponse
    {
        abort_if($note->user_id !== $request->user()->id, 403);

        $note->delete();

        return back()->with('success', 'Entry deleted.');
    }

    // ── Export PDF ───────────────────────────────────────────────
    public function exportPdf(Request $request): HttpResponse
    {
        $year = (int) $request->query('year', now()->year);
        $user = $request->user();

        $notes = Note::where('user_id', $user->id)
            ->whereNotNull('note_date')
            ->whereBetween('note_date', ["{$year}-01-01", "{$year}-12-31"])
            ->orderBy('note_date')
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn ($n) => $n->note_date->format('Y-m-d'));

        $pdf = Pdf::loadView('notes.diary_pdf', [
            'user'  => $user,
            'year'  => $year,
            'notes' => $notes,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("my-diary-{$year}.pdf");
    }

    // ── Send to Telegram ────────────────────────────────────────
    private function sendToTelegram($user, Note $note): ?bool
    {
        if (! $user->telegram_bot_token || ! $user->telegram_chat_id) {
            return null;
        }

        $moodEmoji = [
            'happy' => '😊', 'sad' => '😢', 'angry' => '😠',
            'calm' => '😌', 'excited' => '🤩',
        ];

        $mood    = $note->mood ? ($moodEmoji[$note->mood] ?? '') . ' ' . ucfirst($note->mood) : '';
        $content = strip_tags($note->content ?? '');
        $date    = Carbon::parse($note->note_date)->format('l, F j, Y');
        $time    = $note->created_at ? $note->created_at->format('H:i') : now()->format('H:i');

        $text = "📔 *New Diary Entry*\n\n"
            . "📅 {$date} at {$time}\n"
            . ($mood ? "🎭 {$mood}\n" : '')
            . "📝 *{$note->title}*\n\n"
            . ($content ? $content : '_No content_');

        try {
            $response = Http::post(
                "https://api.telegram.org/bot{$user->telegram_bot_token}/sendMessage",
                [
                    'chat_id'    => $user->telegram_chat_id,
                    'text'       => $text,
                    'parse_mode' => 'Markdown',
                ]
            );

            if ($response->successful() && $response->json('ok')) {
                return true;
            }

            Log::warning('Telegram send failed: ' . ($response->json('description') ?? $response->body()));
            return false;
        } catch (\Throwable $e) {
            Log::warning('Telegram send failed: ' . $e->getMessage());
            return false;
        }
    }

    // ── Register Telegram webhook ─────────────────────────────────
    public function registerWebhook(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->telegram_bot_token || ! $user->telegram_chat_id) {
            return response()->json(['success' => false, 'message' => 'Save your bot token and chat ID first.']);
        }

        if (! $user->telegram_webhook_secret) {
            $user->telegram_webhook_secret = bin2hex(random_bytes(16));
            $user->save();
        }

        $webhookUrl = url("/telegram/webhook/{$user->id}/{$user->telegram_webhook_secret}");

        try {
            $response = Http::post(
                "https://api.telegram.org/bot{$user->telegram_bot_token}/setWebhook",
                [
                    'url'                  => $webhookUrl,
                    'allowed_updates'      => ['message'],
                    'drop_pending_updates' => false,
                ]
            );

            if ($response->successful() && $response->json('ok')) {
                return response()->json(['success' => true, 'message' => 'Webhook registered! Send any message to your bot — it will be saved as a diary entry.']);
            }

            $desc = $response->json('description') ?? 'Unknown error';
            return response()->json(['success' => false, 'message' => "Telegram error: {$desc}"]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()]);
        }
    }

    // ── Handle incoming Telegram webhook message ──────────────────
    public function handleTelegramWebhook(Request $request, int $userId, string $secret): JsonResponse
    {
        $user = \App\Models\User::find($userId);

        if (! $user || ! hash_equals((string) $user->telegram_webhook_secret, $secret)) {
            return response()->json(['ok' => false], 403);
        }

        $update  = $request->json()->all();
        $message = $update['message'] ?? null;

        // Only handle text messages
        if (! $message || empty($message['text'])) {
            return response()->json(['ok' => true]);
        }

        // Verify the message is from the correct chat
        $chatId = (string) ($message['chat']['id'] ?? '');
        if (! $user->telegram_chat_id || ! hash_equals((string) $user->telegram_chat_id, $chatId)) {
            return response()->json(['ok' => false], 403);
        }

        $text = trim($message['text']);

        // Ignore bot commands
        if (str_starts_with($text, '/')) {
            return response()->json(['ok' => true]);
        }

        // Convert plain text to basic HTML for TipTap
        $lines   = explode("\n", $text);
        $content = implode('', array_map(
            fn ($line) => '<p>' . (trim($line) !== '' ? e($line) : '') . '</p>',
            $lines
        ));

        $tz       = $user->timezone ?? 'Asia/Colombo';
        $now      = Carbon::now($tz);
        $noteDate = $now->toDateString();
        $title    = $now->format('l, F j, Y') . ' (Telegram)';

        $user->notes()->create([
            'title'     => $title,
            'content'   => $content,
            'color'     => 'default',
            'note_date' => $noteDate,
            'mood'      => null,
        ]);

        // Send confirmation back to user
        try {
            Http::post(
                "https://api.telegram.org/bot{$user->telegram_bot_token}/sendMessage",
                [
                    'chat_id'    => $user->telegram_chat_id,
                    'text'       => "\u2705 <b>Diary entry saved!</b>\n\n\ud83d\udcc5 {$now->format('l, F j, Y')}\n\u23f0 {$now->format('H:i')} ({$tz})",
                    'parse_mode' => 'HTML',
                ]
            );
        } catch (\Throwable) {
            // silently ignore confirmation failure
        }

        return response()->json(['ok' => true]);
    }

    // ── Test Telegram connection ──────────────────────────────────
    public function testTelegramConnection(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->telegram_bot_token || ! $user->telegram_chat_id) {
            return response()->json(['success' => false, 'message' => 'Bot token or Chat ID not configured. Save them first.']);
        }

        try {
            $response = Http::post(
                "https://api.telegram.org/bot{$user->telegram_bot_token}/sendMessage",
                [
                    'chat_id'    => $user->telegram_chat_id,
                    'text'       => "✅ *Connection test successful\!*\n\nYour diary is connected\. New entries will be sent here automatically\.",
                    'parse_mode' => 'MarkdownV2',
                ]
            );

            if ($response->successful() && $response->json('ok')) {
                return response()->json(['success' => true, 'message' => 'Test message sent! Check your Telegram.']);
            }

            $desc = $response->json('description') ?? 'Unknown error';
            return response()->json(['success' => false, 'message' => "Telegram error: {$desc}"]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Connection failed: ' . $e->getMessage()]);
        }
    }

    // ── Auto-detect Chat ID from getUpdates ───────────────────────
    public function detectChatId(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->telegram_bot_token) {
            return response()->json(['success' => false, 'message' => 'Bot token not configured. Save it first.']);
        }

        try {
            $response = Http::get(
                "https://api.telegram.org/bot{$user->telegram_bot_token}/getUpdates",
                ['limit' => 10, 'offset' => -10]
            );

            if (! $response->successful() || ! $response->json('ok')) {
                $desc = $response->json('description') ?? 'Unknown error';
                return response()->json(['success' => false, 'message' => "Telegram error: {$desc}"]);
            }

            $updates = $response->json('result', []);
            if (empty($updates)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No messages found. Please send any message to your bot first, then try again.',
                ]);
            }

            $latest = end($updates);
            $chatId = $latest['message']['chat']['id']
                ?? $latest['edited_message']['chat']['id']
                ?? $latest['callback_query']['message']['chat']['id']
                ?? null;

            if (! $chatId) {
                return response()->json(['success' => false, 'message' => 'Could not read Chat ID. Try sending a message to your bot.']);
            }

            return response()->json(['success' => true, 'chatId' => (string) $chatId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Connection failed: ' . $e->getMessage()]);
        }
    }
}
