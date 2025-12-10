<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorrectionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'user_id',
        'comment',
        'new_date',
        'new_clock_in',
        'new_clock_out',
        'application_date',
        'status',
        'created_by_admin',
    ];

    protected $casts = [
        'new_date' => 'date',
        'new_clock_in' => 'datetime',
        'new_clock_out' => 'datetime',
        'application_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function correctionBreakTimes()
    {
        return $this->hasMany(CorrectionBreakTime::class);
    }

}
