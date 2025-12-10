<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\BreakTime;

class BreakTimesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        Attendance::whereNotNull('clock_in')->get()->each(function ($attendance) {

            $makeFirstBreak = rand(0, 1) === 1;

            if ($makeFirstBreak) {
                BreakTime::factory()->create([
                    'attendance_id' => $attendance->id,
                ]);

                $additionalBreakCount = rand(0, 2);
                for ($i = 0; $i < $additionalBreakCount; $i++) {
                    BreakTime::factory()->create([
                        'attendance_id' => $attendance->id,
                    ]);
                }
            }
            $attendance->calculateTotals();
            $attendance->save();
        });
    }
}
