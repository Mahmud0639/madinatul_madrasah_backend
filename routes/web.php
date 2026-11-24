<?php

use App\Models\MadrasahClass;
use App\Models\MadrasahRoutine;
use Illuminate\Http\Request;
use App\Http\Controllers\DailyLessonPrintController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/routine-print', function (Request $request) {
    $classId = $request->query('class_id');

    $query = MadrasahRoutine::with('madrasahClass')
        ->orderBy('madrasah_class_id', 'asc')
        ->orderBy('start_time', 'asc');

    if ($classId) {
        $query->where('madrasah_class_id', $classId);
    }

    $routines = $query->get();
    $selectedClass = $classId ? MadrasahClass::find($classId) : null;

    return view('prints.routine', compact('routines', 'selectedClass'));
})->name('routine.print')->middleware('auth');


Route::middleware('auth')->get('/lessons/print', [DailyLessonPrintController::class, 'print'])->name('lessons.print');