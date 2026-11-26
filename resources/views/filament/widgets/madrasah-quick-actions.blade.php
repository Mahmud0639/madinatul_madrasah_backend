<x-filament-widgets::widget>
    <div style="border-radius:16px;padding:18px 22px;background:linear-gradient(120deg, #064e3b 0%, #047857 65%, #059669 100%);color:#ffffff;box-shadow:0 8px 24px rgba(6,78,59,0.16);">
        <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:14px;">
            <div>
                <div style="font-size:12px;opacity:0.85;letter-spacing:0.3px;">
                    📅 {{ now()->format('d M, Y') }} · একাডেমিক ড্যাশবোর্ড
                </div>
                <h2 style="font-size:20px;font-weight:800;margin:4px 0 2px;color:#ffffff;">
                    মদিনাতুল উলূম মাদরাসা ম্যানেজমেন্ট সিস্টেম
                </h2>
                <p style="font-size:13px;opacity:0.9;margin:0;color:#ecfdf5;">
                    শিক্ষার্থী, হাজিরা, পরীক্ষার ফলাফল ও ফি/বকেয়ার সার্বিক নিয়ন্ত্রণ এক জায়গায়
                </p>
            </div>

            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                <a href="{{ url('/admin/students') }}"
                   style="padding:8px 14px;border-radius:10px;background:rgba(255,255,255,0.16);color:#fff;font-size:12.5px;font-weight:700;text-decoration:none;border:1px solid rgba(255,255,255,0.25);">
                    🎓 শিক্ষার্থী তালিকা
                </a>
                <a href="{{ url('/admin/fees/create') }}"
                   style="padding:8px 14px;border-radius:10px;background:#ffffff;color:#064e3b;font-size:12.5px;font-weight:800;text-decoration:none;">
                    + নতুন ফি যুক্ত করুন
                </a>
                <a href="{{ url('/admin/exam-results') }}"
                   style="padding:8px 14px;border-radius:10px;background:rgba(255,255,255,0.16);color:#fff;font-size:12.5px;font-weight:700;text-decoration:none;border:1px solid rgba(255,255,255,0.25);">
                    📝 পরীক্ষার ফলাফল
                </a>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>