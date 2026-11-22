<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ক্লাস রুটিন - {{ $selectedClass ? $selectedClass->name : 'সকল ক্লাস' }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        @font-face {
            font-family: 'Kalpurush';
            src: url('/fonts/Kalpurush.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'Kalpurush';
            src: url('/fonts/Kalpurush.ttf') format('truetype');
            font-weight: bold;
            font-style: normal;
        }

        body, th, td, button {
            font-family: 'Kalpurush', 'Hind Siliguri', sans-serif;
            color: #111827;
        }
        body {
            background-color: #f3f4f6;
            margin: 0;
            padding: 20px;
        }
        .toolbar {
            max-width: 1100px;
            margin: 0 auto 20px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .btn-download {
            background-color: #059669;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            font-family: 'Kalpurush', 'Hind Siliguri', sans-serif;
        }
        .btn-download:hover { background-color: #047857; }

        .page-container {
            max-width: 1100px;
            margin: 0 auto;
            background: #fff;
            padding: 28px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            padding: 16px 20px 18px;
            margin-bottom: 22px;
            border-bottom: 3px solid #065f46;
            background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 100%);
            border-radius: 10px 10px 0 0;
        }
        .header h1 {
            font-family: 'Hind Siliguri', 'Kalpurush', sans-serif !important;
            font-size: 30px;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: #064e3b;
            margin: 0 0 6px 0;
        }
        .header p {
            font-family: 'Kalpurush', 'Hind Siliguri', sans-serif !important;
            font-size: 16px;
            font-weight: 600;
            color: #047857;
            margin: 0;
            display: inline-block;
            padding: 3px 14px;
            background-color: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 999px;
        }

        .class-block {
            margin-bottom: 26px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            overflow: hidden;
            break-inside: avoid;
        }
        .class-title {
            background-color: #065f46;
            color: #ffffff;
            padding: 10px 16px;
            font-size: 18px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            text-align: center;
            font-size: 13.5px;
            vertical-align: middle;
        }
        th {
            background-color: #ecfdf5;
            color: #064e3b;
            font-weight: bold;
        }
        .day-cell {
            background-color: #f8fafc;
            font-weight: bold;
            color: #0f172a;
            white-space: nowrap;
        }
        .slot-box {
            line-height: 1.35;
        }
        .slot-subject {
            font-weight: bold;
            color: #111827;
            display: block;
        }
        .slot-teacher {
            font-size: 12px;
            color: #4b5563;
            display: block;
        }
        .slot-time {
            font-size: 11px;
            color: #047857;
            display: block;
            margin-top: 2px;
        }
        .break-cell {
            background-color: #fef3c7 !important;
            color: #92400e !important;
            font-weight: bold;
        }

        @media print {
            body { background: none; padding: 0; }
            .toolbar { display: none; }
            .page-container { box-shadow: none; padding: 0; max-width: 100%; }
            .class-block { page-break-inside: avoid; }
            @page { size: A4 landscape; margin: 10mm; }
        }
    </style>
</head>
<body>

    @php
        $daysOrder = ['শনিবার', 'রবিবার', 'সোমবার', 'মঙ্গলবার', 'বুধবার', 'বৃহস্পতিবার', 'শুক্রবার'];

        // ক্লাস অনুযায়ী রুটিন গ্রুপিং
        $groupedByClass = collect($routines)->groupBy(function ($item) {
            return $item->madrasahClass->name ?? 'অনির্ধারিত ক্লাস';
        });
    @endphp

    <div class="toolbar">
        <span><strong>প্রিভিউ মোড:</strong> ক্লাসভিত্তিক গুচ্ছ রুটিন প্রিভিউ</span>
        <button class="btn-download" onclick="window.print()">📥 পিডিএফ ডাউনলোড / প্রিন্ট করুন</button>
    </div>

    <div class="page-container">
        <div class="header">
            <h1>মাদিনাতুল উলুম মাদ্রাসা</h1>
            <p>সাপ্তাহিক ক্লাসভিত্তিক রুটিন ও সময়সূচি</p>
        </div>

        @forelse($groupedByClass as $className => $classRoutines)
            @php
                // এই ক্লাসের সব পিরিয়ড (সময় ও ক্রম অনুযায়ী সাজানো)
                $periods = $classRoutines->sortBy('start_time')->pluck('period_name')->unique()->values();

                // নির্দিষ্ট বার ও পিরিয়ড অনুযায়ী দ্রুত খুঁজে পাওয়ার ম্যাপ
                $routineMap = [];
                foreach ($classRoutines as $r) {
                    $routineMap[$r->day_name][$r->period_name] = $r;
                }

                // যে বারগুলোতে অন্তত একটি পিরিয়ড আছে
                $activeDays = collect($daysOrder)->filter(function ($day) use ($routineMap) {
                    return isset($routineMap[$day]);
                });
            @endphp

            <div class="class-block">
                <div class="class-title">
                    <span>জামাত / ক্লাস: {{ $className }}</span>
                    <span style="font-size: 13px; font-weight: normal;">মোট পিরিয়ড: {{ $periods->count() }}</span>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th style="width: 110px;">বার / দিন</th>
                            @foreach($periods as $period)
                                <th>{{ $period }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activeDays as $day)
                            <tr>
                                <td class="day-cell">{{ $day }}</td>
                                @foreach($periods as $period)
                                    @php
                                        $item = $routineMap[$day][$period] ?? null;
                                        $isBreak = $item && $item->period_name === 'বিরতি / টিফিন';
                                    @endphp

                                    @if(!$item)
                                        <td>-</td>
                                    @elseif($isBreak)
                                        <td class="break-cell">
                                            বিরতি / টিফিন
                                            @if($item->start_time && $item->end_time)
                                                <span class="slot-time">
                                                    {{ date('h:i A', strtotime($item->start_time)) }} - {{ date('h:i A', strtotime($item->end_time)) }}
                                                </span>
                                            @endif
                                        </td>
                                    @else
                                        <td>
                                            <div class="slot-box">
                                                <span class="slot-subject">{{ $item->subject_name ?? '-' }}</span>
                                                <span class="slot-teacher">{{ $item->teacher_name ?? '-' }}</span>
                                                @if($item->start_time && $item->end_time)
                                                    <span class="slot-time">
                                                        {{ date('h:i A', strtotime($item->start_time)) }} - {{ date('h:i A', strtotime($item->end_time)) }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                    @endif
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <div style="text-align: center; padding: 30px; border: 1px solid #e5e7eb; border-radius: 8px;">
                কোনো রুটিন পাওয়া যায়নি।
            </div>
        @endforelse
    </div>

</body>
</html>
