<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>My Diary — {{ $year }}</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #1a1a1a; background: #fff; }

  .cover {
    text-align: center;
    padding: 80px 40px;
    border-bottom: 3px solid #7c3aed;
    margin-bottom: 40px;
  }
  .cover h1 { font-size: 32px; color: #7c3aed; font-weight: 700; letter-spacing: 1px; }
  .cover .owner { margin-top: 12px; font-size: 14px; color: #555; }
  .cover .year  { margin-top: 6px; font-size: 18px; color: #333; font-weight: 600; }

  .month-heading {
    font-size: 16px;
    font-weight: 700;
    color: #7c3aed;
    border-left: 4px solid #7c3aed;
    padding-left: 10px;
    margin: 28px 0 12px;
    page-break-after: avoid;
  }

  .day-block {
    margin-bottom: 20px;
    padding: 14px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    page-break-inside: avoid;
  }

  .day-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
    border-bottom: 1px solid #f0f0f0;
    padding-bottom: 8px;
  }
  .day-date { font-size: 13px; font-weight: 700; color: #374151; }
  .mood-badge {
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 12px;
    background: #ede9fe;
    color: #6d28d9;
  }

  .note-item { margin-bottom: 12px; }
  .note-title { font-weight: 600; font-size: 13px; color: #111827; margin-bottom: 4px; }
  .note-content { font-size: 11px; color: #4b5563; line-height: 1.6; }
  .note-content p { margin-bottom: 4px; }
  .note-content ul, .note-content ol { padding-left: 18px; margin-bottom: 4px; }
  .note-content li { margin-bottom: 2px; }
  .note-content strong { font-weight: 700; }
  .note-content em { font-style: italic; }
  .note-content hr { border: none; border-top: 1px solid #e5e7eb; margin: 6px 0; }
  .note-separator { border: none; border-top: 1px dashed #e5e7eb; margin: 8px 0; }

  .footer { text-align: center; font-size: 10px; color: #9ca3af; margin-top: 40px; border-top: 1px solid #f0f0f0; padding-top: 12px; }
</style>
</head>
<body>

<div class="cover">
  <h1>📓 My Personal Diary</h1>
  <div class="year">{{ $year }}</div>
  <div class="owner">{{ $user->name }}</div>
</div>

@php
  $moodLabels = [
      'happy'   => '😊 Happy',
      'sad'     => '😔 Sad',
      'angry'   => '😡 Angry',
      'calm'    => '😌 Calm',
      'excited' => '🔥 Excited',
  ];
  $currentMonth = '';
@endphp

@forelse($notes as $date => $dayNotes)
  @php
    $d = \Carbon\Carbon::parse($date);
    $monthKey = $d->format('F Y');
  @endphp

  @if($monthKey !== $currentMonth)
    @php $currentMonth = $monthKey; @endphp
    <div class="month-heading">{{ $monthKey }}</div>
  @endif

  <div class="day-block">
    <div class="day-header">
      <span class="day-date">{{ $d->format('l, d F Y') }}</span>
      @php $mood = $dayNotes->first()?->mood; @endphp
      @if($mood)
        <span class="mood-badge">{{ $moodLabels[$mood] ?? $mood }}</span>
      @endif
    </div>

    @foreach($dayNotes as $i => $note)
      @if($i > 0)
        <hr class="note-separator" />
      @endif
      <div class="note-item">
        @if($note->title)
          <div class="note-title">{{ $note->title }}</div>
        @endif
        @if($note->content)
          <div class="note-content">{!! $note->content !!}</div>
        @endif
      </div>
    @endforeach
  </div>

@empty
  <p style="text-align:center; color:#9ca3af; padding:40px;">No diary entries for {{ $year }}.</p>
@endforelse

<div class="footer">
  Generated on {{ now()->format('d M Y, H:i') }} &bull; PersonalApp
</div>

</body>
</html>
