<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\CorrectionRequest;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Http\Requests\CorrectionRequestForm;
use App\Models\BreakTime;
use App\Models\CorrectionBreakTime;


class AttendanceHistoryController extends Controller
{
    public function list(Request $request)
    {
        $user = Auth::user();

        $month = $request->query('month', date('Y-m'));
        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $attendances = [];
        $date = $startDate->copy();
        while ($date->lte($endDate)) {
            $attendance = Attendance::fetchOrNewWithLatestCorrection(
                $user->id,
                $date->format('Y-m-d'),
                true
            );

            $attendance->calculateTotals();
            $attendances[] = $attendance;
            $date->addDay();
        }

        return view('attendance_list', [
            'attendances' => $attendances,
            'currentMonth' => $startDate->format('Y/m'),
            'currentMonthParam' => $startDate->format('Y-m'),
            'prevMonth' => $startDate->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $startDate->copy()->addMonth()->format('Y-m'),
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = Auth::user();

        if ($id == 0) {
            $workDate = $request->query('work_date');
            $attendance = Attendance::fetchOrNewWithLatestCorrection($user->id, $workDate);
        } else {
            $attendanceRecord = Attendance::findOrFail($id);

            $attendance = Attendance::fetchOrNewWithLatestCorrection($user->id, $attendanceRecord->work_date);
        }
        $attendance->setRelation('user', $user);

        if ($id == 0) {
            $latestRequest = CorrectionRequest::where('user_id', $user->id)
                ->whereDate('new_date', $attendance->work_date)
                ->latest('created_at')
                ->first();
            $isEditable = !$latestRequest || $latestRequest->status !== 'pending';
        } else {
            $hasPending = CorrectionRequest::where('attendance_id', $attendance->id)
                ->where('status', 'pending')
                ->exists();
            $isEditable = !$hasPending;
        }

        $existingBreaks = $attendance->breakTimes->filter(fn($break) => $break->break_in || $break->break_out);
        $attendance->setRelation('breakTimes', $existingBreaks);

        $initialLoad = !session()->hasOldInput();

        return view('attendance_detail', compact('attendance', 'isEditable', 'initialLoad'));
    }

    public function update(CorrectionRequestForm $request, $id)
    {
        $user = Auth::user();

        if ($id == 0) {
            $workDate = $request->input('work_date');

            $correctionRequest = CorrectionRequest::create([
                'user_id' => $user->id,
                'new_date' => $workDate,
                'new_clock_in' => $request->input('clock_in'),
                'new_clock_out' => $request->input('clock_out'),
                'comment' => $request->input('comment'),
                'application_date' => now(),
                'status' => 'pending',
            ]);

            $existingBreaks = collect();
        } else {
            $attendance = Attendance::findOrFail($id);

            $correctionRequest = CorrectionRequest::create([
                'user_id' => $user->id,
                'attendance_id' => $attendance->id,
                'new_date' => $attendance->work_date,
                'new_clock_in' => $request->input('clock_in'),
                'new_clock_out' => $request->input('clock_out'),
                'comment' => $request->input('comment'),
                'application_date' => now(),
                'status' => 'pending',
            ]);

            $existingBreaks = BreakTime::where('attendance_id', $attendance->id)->get();
        }

        $breakIns = $request->input('break_in') ?? [];
        $breakOuts = $request->input('break_out') ?? [];

        $max = max(count($breakIns), $existingBreaks->count());

        for ($i = 0; $i < $max; $i++) {
            $in = $breakIns[$i] ?? null;
            $out = $breakOuts[$i] ?? null;
            $existing = $existingBreaks[$i] ?? null;

            if ($in || $out) {
                CorrectionBreakTime::create([
                    'correction_request_id' => $correctionRequest->id,
                    'new_break_in' => $in,
                    'new_break_out' => $out,
                ]);
            } elseif ($existing && ($in === null && $out === null)) {
                CorrectionBreakTime::create([
                    'correction_request_id' => $correctionRequest->id,
                    'new_break_in' => null,
                    'new_break_out' => null,
                ]);
            }
        }
        $month = isset($attendance)
            ? $attendance->work_date->format('Y-m')
            : substr($request->input('work_date'), 0, 7);

        return redirect()->route('attendance.list', ['month' => $month])
            ->with('success', '修正申請を送信しました');
    }
}

