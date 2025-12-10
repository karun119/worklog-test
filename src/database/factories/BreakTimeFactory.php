<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\BreakTime;
use App\Models\Attendance;
use Carbon\Carbon;

class BreakTimeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    protected $model = BreakTime::class;


    public function definition()
    {
        $attendance = Attendance::whereNotNull('clock_in')->inRandomOrder()->first();

        if ($attendance) {
            $clockIn = Carbon::parse($attendance->clock_in);
            $clockOut = $attendance->clock_out ? Carbon::parse($attendance->clock_out) : $clockIn->copy()->addHours(8);

            $breakIn = $clockIn->copy()->addHours(rand(2, 6))->addMinutes(rand(0, 30));

            $breakOut = $breakIn->copy()->addMinutes(rand(30, 60));

            if ($breakOut->gt($clockOut)) {
                $breakOut = $clockOut->copy()->subMinutes(rand(5, 20));
            }
            $breakIn = Attendance::roundToMinute($breakIn);
            $breakOut = Attendance::roundToMinute($breakOut);
        } else {
            $breakIn = null;
            $breakOut = null;
        }

        return [
            'attendance_id' => $attendance->id ?? null,
            'break_in' => $breakIn,
            'break_out' => $breakOut,
        ];
    }
}
