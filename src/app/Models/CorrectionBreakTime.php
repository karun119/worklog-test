<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorrectionBreakTime extends Model
{
    use HasFactory;

    protected $fillable = [
        'correction_request_id',
        'new_break_in',
        'new_break_out',
    ];

    protected $casts = [
        'new_break_in'  => 'datetime',
        'new_break_out' => 'datetime',
    ];

    public function correctionRequest()
    {
        return $this->belongsTo(CorrectionRequest::class);
    }
}
