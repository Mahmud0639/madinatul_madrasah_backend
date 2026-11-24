<?php

namespace App\Http\Controllers;

use App\Models\DailyLesson;
use App\Models\Department;
use App\Models\MadrasahClass;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DailyLessonPrintController extends Controller
{
    public function print(Request $request)
    {
        $date = $request->get('date', Carbon::now('Asia/Dhaka')->toDateString());
        $classId = $request->get('class_id');
        $departmentId = $request->get('department_id');

        $query = DailyLesson::query()
            ->whereDate('lesson_date', $date)
            ->with(['madrasahClass', 'subject', 'teacher', 'studentStatuses.student']);

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        if ($classId) {
            $query->where('madrasah_class_id', $classId);
        }

        $lessons = $query->orderBy('madrasah_class_id')->orderBy('id')->get();

        return view('lessons.print', [
            'lessons' => $lessons,
            'date' => $date,
            'selectedClass' => $classId ? MadrasahClass::find($classId) : null,
            'selectedDepartment' => $departmentId ? Department::find($departmentId) : null,
        ]);
    }
}