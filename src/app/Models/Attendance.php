<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Http\Request;
class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in',
        'clock_out',
        'total_work_time',
        'total_break_time',
        'comment',
    ];

    protected $casts = [
        'work_date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'total_work_time' => 'datetime',
        'total_break_time' => 'datetime',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class);
    }

    public function correctionRequest()
    {
        return $this->hasOne(CorrectionRequest::class);
    }


    public static function roundToMinute($timeString)
    {
        if (!$timeString) return null;

        $dt = Carbon::parse($timeString);
        $dt->second(0);
        return $dt->toTimeString();
    }

    public function fillFromRequest(Request $request)
    {
        $this->clock_in  = self::roundToMinute($request->clock_in);
        $this->clock_out = self::roundToMinute($request->clock_out);
        $this->comment = $request->comment ?? '';
        $this->calculateTotals();

        return $this;
    }

    public function calculateTotals()
    {
        $workSeconds = 0;
        if ($this->clock_in && $this->clock_out) {
            $workSeconds = strtotime($this->clock_out) - strtotime($this->clock_in);
        }
        $breakSeconds = $this->breakTimes->reduce(function ($carry, $break) {
            if ($break->break_in && $break->break_out) {
                return $carry + (strtotime($break->break_out) - strtotime($break->break_in));
            }
            return $carry;
        }, 0);
        $total = $workSeconds - $breakSeconds;
        $this->total_work_time = $total > 0 ? gmdate('H:i', $total) : null;
        $this->total_break_time = $breakSeconds > 0 ? gmdate('H:i', $breakSeconds) : null;

        return $this;
    }

    /**
     * 一般ユーザー勤怠一覧用：最新の修正申請（new系）を反映
     */
    public static function fetchOrNewWithLatestCorrection($userId, $date, $onlyApproved = false): self
    {
        $attendance = self::where('user_id', $userId)
            ->where('work_date', $date)
            ->with('breakTimes')
            ->first();
        if (!$attendance) {
            $attendance = new self([
                'id' => null,
                'user_id' => $userId,
                'work_date' => $date,
                'clock_in' => null,
                'clock_out' => null,
                'comment' => null,
            ]);
            $attendance->setRelation('breakTimes', collect([]));
        }
        $query = CorrectionRequest::where('user_id', $userId)
            ->whereDate('new_date', $date);
        if ($onlyApproved) {
            $query->where('status', 'approved');
        }
        $latestRequest = $query->latest('created_at')->first();
        if ($latestRequest) {
            $attendance->clock_in  = $latestRequest->new_clock_in ?? $attendance->clock_in;
            $attendance->clock_out = $latestRequest->new_clock_out ?? $attendance->clock_out;
            $attendance->comment   = $latestRequest->comment ?? $attendance->comment;
            $correctionBreaks = $latestRequest->correctionBreakTimes->map(fn($cbt) => (object)[
                'break_in' => $cbt->new_break_in,
                'break_out' => $cbt->new_break_out,
            ]);

            $attendance->setRelation('breakTimes', $correctionBreaks);
        }

        return $attendance;
    }

    /**
     * 管理者一覧画面用：承認済み修正のみ勤怠データに反映
     * 空勤怠も作成
     */
    public static function fetchOrNewWithLatestApprovedCorrectionForAdmin($userId, $date): self
    {
        $attendance = self::where('user_id', $userId)
            ->where('work_date', $date)
            ->with('breakTimes')
            ->first();
        if (!$attendance) {
            $attendance = new self([
                'id' => null,
                'user_id' => $userId,
                'work_date' => $date,
                'clock_in' => null,
                'clock_out' => null,
                'comment' => null,
            ]);
            $attendance->setRelation('breakTimes', collect([]));
        }
        $latestRequest = CorrectionRequest::where('user_id', $userId)
            ->whereDate('new_date', $date)
            ->where('status', 'approved')
            ->latest('created_at')
            ->first();
        if ($latestRequest) {
            $attendance->clock_in  = $latestRequest->new_clock_in ?? $attendance->clock_in;
            $attendance->clock_out = $latestRequest->new_clock_out ?? $attendance->clock_out;
            $attendance->comment   = $latestRequest->comment ?? $attendance->comment;
            $correctionBreaks = $latestRequest->correctionBreakTimes->map(fn($cbt) => (object)[
                'break_in' => $cbt->new_break_in,
                'break_out' => $cbt->new_break_out,
            ]);
            $attendance->setRelation('breakTimes', $correctionBreaks);
        }

        return $attendance;
    }

    /**
     * 管理者詳細画面用：最新の修正申請（承認済み・未承認）を反映
     * 空勤怠でも常に最新情報を取得
     */
    public static function fetchOrNewWithLatestCorrectionForAdmin($userId, $date): self
    {
        $attendance = self::where('user_id', $userId)
            ->where('work_date', $date)
            ->with('breakTimes')
            ->first();
        if (!$attendance) {
            $attendance = new self([
                'id' => null,
                'user_id' => $userId,
                'work_date' => $date,
                'clock_in' => null,
                'clock_out' => null,
                'comment' => null,
            ]);
            $attendance->setRelation('breakTimes', collect([]));
        }
        $latestRequest = CorrectionRequest::where('user_id', $userId)
            ->whereDate('new_date', $date)
            ->latest('created_at')
            ->first();
        if ($latestRequest) {
            $attendance->clock_in  = $latestRequest->new_clock_in ?? $attendance->clock_in;
            $attendance->clock_out = $latestRequest->new_clock_out ?? $attendance->clock_out;
            $attendance->comment   = $latestRequest->comment ?? $attendance->comment;
            $correctionBreaks = $latestRequest->correctionBreakTimes->map(fn($cbt) => (object)[
                'break_in' => $cbt->new_break_in,
                'break_out' => $cbt->new_break_out,
            ]);
            $attendance->setRelation('breakTimes', $correctionBreaks);
        }

        return $attendance;
    }
}
