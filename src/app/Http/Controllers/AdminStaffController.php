<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;


class AdminStaffController extends Controller
{
    public function index()
    {
        $staffs = User::where('admin_status', 'general')->get();
        return view('admin.staff_list', compact('staffs'));
    }

    public function attendanceIndex(Request $request, $id)
    {
        $staff = User::findOrFail($id);
        $month = $request->query('month', date('Y-m'));
        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $attendances = [];
        $date = $startDate->copy();
        while ($date->lte($endDate)) {
            $attendance = Attendance::fetchOrNewWithLatestApprovedCorrectionForAdmin($staff->id, $date->format('Y-m-d'));
            $attendance->calculateTotals();
            $attendances[] = $attendance;
            $date->addDay();
        }

        if ($request->has('export')) {
            return $this->exportCsv($attendances, $staff, $month);
        }

        return view('admin.staff_attendance', [
            'staff' => $staff,
            'attendances' => $attendances,
            'currentMonth' => $startDate->format('Y/m'),
            'currentMonthParam' => $startDate->format('Y-m'),
            'prevMonth' => $startDate->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $startDate->copy()->addMonth()->format('Y-m'),
        ]);
    }
    /**
     * CSV出力
     */
    private function exportCsv(array $attendances, User $staff, string $month): StreamedResponse
    {
        $filename = "{$staff->name}_{$month}_attendance.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($attendances) {
            $handle = fopen('php://output', 'w');
            fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, ['日付', '出勤', '退勤', '休憩', '合計', 'コメント']);
            foreach ($attendances as $attendance) {

                $clockIn = optional($attendance->clock_in)->format('H:i');

                $clockOut = optional($attendance->clock_out)->format('H:i');
                $clockOut = ($clockOut === '23:59') ? '' : $clockOut;

                $break = optional($attendance->total_break_time)->format('G:i');

                $total = optional($attendance->total_work_time)->format('G:i');
                $total = ($clockOut === '') ? '' : $total;

                fputcsv($handle, [
                    optional($attendance->work_date)->isoFormat('MM/DD(ddd)'),
                    $clockIn,
                    $clockOut,
                    $break,
                    $total,
                    $attendance->comment,
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
