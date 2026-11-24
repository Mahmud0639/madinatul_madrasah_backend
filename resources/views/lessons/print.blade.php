<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>দৈনিক পড়া শিট - {{ $date }}</title>
    <style>
        body { font-family: 'Kalpurush', Arial, sans-serif; margin: 24px; color: #111; }
        .header { text-align: center; border-bottom: 2px solid #222; padding-bottom: 10px; margin-bottom: 18px; }
        .meta { display: flex; justify-content: space-between; margin-bottom: 16px; font-size: 14px; }
        .card { border: 1px solid #ccc; border-radius: 6px; padding: 12px 14px; margin-bottom: 12px; page-break-inside: avoid; }
        .card-title { font-weight: bold; font-size: 16px; margin-bottom: 6px; }
        .desc { white-space: pre-line; font-size: 14px; line-height: 1.55; }
        .badge { display: inline-block; background: #eef2ff; padding: 2px 8px; border-radius: 4px; font-size: 12px; margin-right: 6px; }
        .print-btn { margin-bottom: 16px; padding: 8px 16px; cursor: pointer; }
        @media print { .print-btn { display: none; } body { margin: 10px; } }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">প্রিন্ট / PDF সংরক্ষণ করুন</button>

    <div class="header">
        <h2>দৈনিক পড়া / সবক শিট</h2>
        <div>তারিখ: <strong>{{ $date }}</strong></div>
    </div>

    <div class="meta">
        <div>বিভাগ: <strong>{{ $selectedDepartment->name ?? 'সকল বিভাগ' }}</strong></div>
        <div>ক্লাস / জামাত: <strong>{{ $selectedClass->name ?? 'সকল ক্লাস' }}</strong></div>
    </div>

    @forelse($lessons as $lesson)
        <div class="card">
            <div class="card-title">
                <span class="badge">{{ $lesson->madrasahClass->name ?? '' }}</span>
                <span class="badge">{{ $lesson->subject_name ?? 'বিষয়' }}</span>
                {{ $lesson->title }}
            </div>
            <div class="desc">{{ $lesson->description }}</div>
            @if($lesson->target_type === 'specific_students')
                <div style="margin-top: 8px; font-size: 13px; color: #b45309;">
                    <strong>নির্দিষ্ট ছাত্র:</strong>
                    {{ $lesson->studentStatuses->map(fn($s) => $s->student->name ?? '')->filter()->implode(', ') }}
                </div>
            @endif
        </div>
    @empty
        <p>এই তারিখের জন্য কোনো পড়া পাওয়া যায়নি।</p>
    @endforelse
</body>
</html>