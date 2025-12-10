<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    protected $model = Attendance::class;

    public function definition()
    {
        $workDate = $this->attributes['work_date'] ?? $this->faker->dateTimeBetween('-4 months', 'now')->format('Y-m-d');

        $didClockIn = rand(1, 20) !== 1;

        $clockIn = $didClockIn ? Carbon::parse('09:00')->addMinutes(rand(0, 60))->second(0) : null;
        $clockOut = $didClockIn ? ($clockIn ? $clockIn->copy()->addHours(8)->addMinutes(rand(0, 30))->second(0) : null) : null;

        return [
            'work_date' => $workDate,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => '',
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Attendance $attendance) {
            $attendance->calculateTotals();
            $attendance->save();
        });
    }
}
