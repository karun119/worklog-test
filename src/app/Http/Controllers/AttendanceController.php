<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\User;


class AttendanceController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $today = Carbon::today()->toDateString();
        $yesterday = Carbon::yesterday()->toDateString();

        $yesterdayAttendance = Attendance::where('user_id', $user->id)
            ->where('work_date', $yesterday)
            ->whereNull('clock_out')
            ->first();

        if ($yesterdayAttendance) {

            $yesterdayAttendance->clock_out = Carbon::parse($yesterdayAttendance->clock_in)
                ->setTime(23, 59, 0);
            $yesterdayAttendance->save();

            $user->attendance_status = 'after_work';
            $user->save();
        }

        $attendance = Attendance::where('user_id', $user->id)
            ->where('work_date', $today)
            ->first();

        if (!$attendance) {
            $user->attendance_status = 'before_work';
            $user->save();
        }

        $freshUser = User::find($user->id);
        $statusMap = [
            'before_work' => '勤務外',
            'working'     => '出勤中',
            'break'       => '休憩中',
            'after_work'  => '退勤済',
        ];
        $status = $statusMap[$freshUser->attendance_status] ?? '勤務外';

        if (!$attendance || $attendance->work_date->toDateString() !== $today) {
            $status = '勤務外';
        }

        return view('attendance', compact('status'));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $action = $request->input('action');
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::firstOrCreate(
            ['user_id' => $user->id, 'work_date' => $today],
            ['comment' => '']
        );

        if (($action === 'clock_in' && $attendance->clock_in) ||
            ($action === 'clock_out' && $attendance->clock_out)
        ) {

            return redirect()->route('attendance.index');
        }

        switch ($action) {
            case 'clock_in':
                $attendance->clock_in = Attendance::roundToMinute(now());
                $user->attendance_status = 'working';
                break;

            case 'break_in':
                $attendance->breakTimes()->create([
                    'break_in' => Attendance::roundToMinute(now()),
                    'break_out' => null,
                ]);
                $user->attendance_status = 'break';
                break;

            case 'break_out':
                $lastBreak = $attendance->breakTimes()->whereNull('break_out')->latest()->first();
                if ($lastBreak) {
                    $lastBreak->break_out = Attendance::roundToMinute(now());
                    $lastBreak->save();
                }
                $user->attendance_status = 'working';
                break;

            case 'clock_out':
                $attendance->clock_out = Attendance::roundToMinute(now());
                $user->attendance_status = 'after_work';
                break;
        }
        $attendance->calculateTotals();
        $attendance->save();
        $user->save();

        return redirect()->route('attendance.index');
    }
}
