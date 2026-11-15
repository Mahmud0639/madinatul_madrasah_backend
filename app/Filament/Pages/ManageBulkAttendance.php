<?php

namespace App\Filament\Pages;

use App\Models\MadrasahClass;
use App\Models\Student;
use App\Models\Attendance;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Notifications\Notification;

class ManageBulkAttendance extends Page implements HasForms
{
    use InteractsWithForms;

    // protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    // protected static ?string $navigationLabel = 'বাল্ক হাজিরা (Bulk Attendance)';
    protected static ?string $slug = 'bulk-attendance';
    protected static string $view = 'filament.pages.manage-bulk-attendance';

    protected static ?string $navigationGroup = 'হাজিরা ব্যবস্থাপনা';
    protected static ?string $navigationLabel = 'বাল্ক হাজিরা (ছাত্র-ছাত্রী)';
    protected static ?string $navigationIcon = 'heroicon-o-bolt';
    protected static ?int $navigationSort = 3;
    

    public ?array $data = [];
    public $students = [];
    public array $attendanceStatuses = [];
    public array $inTimes = [];
    public array $outTimes = [];
    public bool $attendanceExistsForDate = false;

    public function mount(): void
    {
        $this->form->fill([
            'date' => now()->format('Y-m-d'),
            'default_in_time' => '08:00:00',
            'default_out_time' => '14:00:00',
        ]);

        $this->loadStudents();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('jamatu_class')
                    ->label('জামাত/ক্লাস নির্বাচন করুন')
                    ->options(MadrasahClass::pluck('name', 'name')->toArray())
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->loadStudents()),

                Forms\Components\DatePicker::make('date')
                    ->label('তারিখ')
                    ->default(now())
                    ->maxDate(now()) // ভবিষ্যতের তারিখ বন্ধ করা
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->loadStudents()),

                Forms\Components\TimePicker::make('default_in_time')
                    ->label('ডিফল্ট প্রবেশের সময় (In-Time)')
                    ->default('08:00')
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->updateDefaultTimes()),

                Forms\Components\TimePicker::make('default_out_time')
                    ->label('ডিফল্ট প্রস্থানের সময় (Out-Time)')
                    ->default('14:00')
                    ->live()
                    ->afterStateUpdated(fn () => $this->updateDefaultTimes()),
            ])
            ->columns(2)
            ->statePath('data');
    }

    public function loadStudents()
    {
        $class = $this->data['jamatu_class'] ?? null;
        $date = $this->data['date'] ?? now()->format('Y-m-d');
        $defaultIn = $this->data['default_in_time'] ?? '08:00:00';
        $defaultOut = $this->data['default_out_time'] ?? '14:00:00';

        if ($class) {
            // ক্লাসের মোট ছাত্র নিয়ে আসা
            $allStudents = Student::where('jamatu_class', $class)->get();

            if ($allStudents->isEmpty()) {
                $this->students = [];
                $this->attendanceExistsForDate = false;
                return;
            }

            // চেক করা যাক এই নির্দিষ্ট তারিখে এই ক্লাসের কোনো হাজিরা রেকর্ড ডেটাবেজে আছে কি না
            $existingAttendances = Attendance::where('date', $date)
                ->whereIn('student_id', $allStudents->pluck('id'))
                ->get()
                ->keyBy('student_id');

            // যদি এই তারিখে কোনো হাজিরা রেকর্ড না থাকে
            if ($existingAttendances->isEmpty()) {
                $this->students = []; // কোনো তালিকা দেখাবে না, খালি থাকবে
                $this->attendanceExistsForDate = false;
            } else {
                // যদি রেকর্ড থাকে, তবেই শুধু ছাত্র তালিকা ও ওই তারিখের ডাটা লোড হবে (এডিট করার জন্য)
                $this->students = $allStudents;
                $this->attendanceExistsForDate = true;

                foreach ($allStudents as $student) {
                    $attendance = $existingAttendances->get($student->id);

                    $this->attendanceStatuses[$student->id] = $attendance ? $attendance->status : 'present';
                    $this->inTimes[$student->id] = $attendance ? $attendance->in_time : $defaultIn;
                    $this->outTimes[$student->id] = $attendance ? $attendance->out_time : $defaultOut;
                }
            }
        } else {
            $this->students = [];
            $this->attendanceExistsForDate = false;
        }
    }

    // নতুন হাজিরা এন্ট্রি করার জন্য বা ফাঁকা দিনে সাবমিট করার সময় সক্রিয় করার ফাংশন
    public function createNewAttendanceForDate()
    {
        $class = $this->data['jamatu_class'] ?? null;
        $defaultIn = $this->data['default_in_time'] ?? '08:00:00';
        $defaultOut = $this->data['default_out_time'] ?? '14:00:00';

        if ($class) {
            $allStudents = Student::where('jamatu_class', $class)->get();
            $this->students = $allStudents;
            $this->attendanceExistsForDate = false;

            foreach ($allStudents as $student) {
                $this->attendanceStatuses[$student->id] = 'present';
                $this->inTimes[$student->id] = $defaultIn;
                $this->outTimes[$student->id] = $defaultOut;
            }
        }
    }

    public function updateDefaultTimes()
    {
        $defaultIn = $this->data['default_in_time'] ?? '08:00:00';
        $defaultOut = $this->data['default_out_time'] ?? '14:00:00';

        foreach ($this->students as $student) {
            $this->inTimes[$student->id] = $defaultIn;
            $this->outTimes[$student->id] = $defaultOut;
        }
    }

    public function submit()
    {
        $formData = $this->form->getState();
        $date = $formData['date'];
        $class = $formData['jamatu_class'] ?? null;

        if (!$class || empty($this->students)) {
            Notification::make()
                ->title('দয়া করে সঠিক ক্লাস সিলেক্ট করুন এবং ছাত্র তালিকা চেক করুন।')
                ->danger()
                ->send();
            return;
        }

        foreach ($this->students as $student) {
            $status = $this->attendanceStatuses[$student->id] ?? 'present';
            $inTime = $this->inTimes[$student->id] ?? ($formData['default_in_time'] ?? null);
            $outTime = $this->outTimes[$student->id] ?? ($formData['default_out_time'] ?? null);

            Attendance::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'date' => $date,
                ],
                [
                    'status' => $status,
                    'in_time' => $inTime,
                    'out_time' => $outTime,
                ]
            );
        }

        $this->attendanceExistsForDate = true;

        Notification::make()
            ->title('আলহামদুলিল্লাহ! উপস্থিতি সফলভাবে সংরক্ষিত হয়েছে।')
            ->success()
            ->send();
    }
}