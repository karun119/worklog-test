<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use App\Models\CorrectionRequest;
use App\Models\CorrectionBreakTime;
use App\Http\Requests\AdminCorrectionRequest;


class AdminController extends Controller
{
    public function index(Request $request)
    {
        $currentDate = $request->query('date')
            ? Carbon::parse($request->query('date'))
            : Carbon::today();

        $prevDate = $currentDate->copy()->subDay();
        $nextDate = $currentDate->copy()->addDay();
        $isToday = $currentDate->isToday();

        $attendances = User::where('admin_status', 'general')
            ->get()
            ->flatMap(function ($user) use ($currentDate) {
            $attendance = Attendance::fetchOrNewWithLatestApprovedCorrectionForAdmin(
                $user->id,
                $currentDate->format('Y-m-d'),
            );
                $attendance->calculateTotals();
                return [$attendance];
            });

        return view('admin.attendance_list', [
            'attendances' => $attendances,
            'currentDate' => $currentDate,
            'prevDate' => $prevDate,
            'nextDate' => $nextDate,
            'isToday'      => $isToday,
        ]);
    }

    public function show(Request $request, $id)
    {
        if ($id == 0) {
            $userId = $request->query('user_id');
            $workDate = $request->query('work_date');
        } else {
            $attendanceBase = Attendance::findOrFail($id);
            $userId = $attendanceBase->user_id;
            $workDate = $attendanceBase->work_date;
        }
        $attendance = Attendance::fetchOrNewWithLatestCorrectionForAdmin($userId, $workDate);

        if ($attendance->id) {
            $hasPending = CorrectionRequest::where('attendance_id', $attendance->id)
                ->where('status', 'pending')
                ->exists();
        } else {
            $hasPending = CorrectionRequest::where('user_id', $attendance->user_id)
                ->whereDate('new_date', $attendance->work_date)
                ->where('status', 'pending')
                ->exists();
        }
        $isEditable = !$hasPending;
        $existingBreaks = $attendance->breakTimes->filter(fn($break) => $break->break_in || $break->break_out);
        $attendance->setRelation('breakTimes', $existingBreaks);
        $initialLoad = !session()->hasOldInput();

        return view('admin.attendance_detail', compact('attendance', 'isEditable', 'initialLoad'));
    }

    public function update(AdminCorrectionRequest $request, $id)
    {
        if ($id == 0) {
            $workDate = $request->input('work_date');
            $userId  = $request->input('user_id');
            $attendance = Attendance::firstOrCreate(
                ['user_id' => $userId, 'work_date' => $workDate],
                ['clock_in' => null, 'clock_out' => null, 'comment' => '']
            );

            $existingBreaks = collect();
        } else {
            $attendance = Attendance::findOrFail($id);
            $existingBreaks = $attendance->breakTimes;
        }
        $correctionRequest = CorrectionRequest::create([
            'user_id' => $attendance->user_id,
            'attendance_id' => $attendance->id,
            'new_date' => $attendance->work_date,
            'new_clock_in' => $request->input('clock_in'),
            'new_clock_out' => $request->input('clock_out'),
            'comment' => $request->input('comment'),
            'application_date' => now(),
            'status' => 'approved',
            'created_by_admin' => true,
        ]);
        $breakIns  = $request->input('break_in') ?? [];
        $breakOuts = $request->input('break_out') ?? [];
        $max       = max(count($breakIns), $existingBreaks->count());

        for ($i = 0; $i < $max; $i++) {
            $in       = $breakIns[$i] ?? null;
            $out      = $breakOuts[$i] ?? null;
            $existing = $existingBreaks[$i] ?? null;

            if ($in || $out) {
                CorrectionBreakTime::create([
                    'correction_request_id' => $correctionRequest->id,
                    'new_break_in' => $in,
                    'new_break_out' => $out,
                ]);
            } elseif ($existing && $in === null && $out === null) {
                CorrectionBreakTime::create([
                    'correction_request_id' => $correctionRequest->id,
                    'new_break_in' => null,
                    'new_break_out' => null,
                ]);
            }
        }
        $date = $attendance->work_date->format('Y-m-d');

        return redirect()->route('admin.attendance.list', ['date' => $date])
            ->with('success', '勤怠を修正しました');
    }
}
