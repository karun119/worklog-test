<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

class AttendancesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = User::where('admin_status', 'general')->get();

        foreach ($users as $user) {
            $startDate = Carbon::now()->subMonths(4)->addMonth()->startOfMonth();
            $endDate = Carbon::yesterday()->endOfDay();

            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                Attendance::factory()->state([
                    'user_id' => $user->id,
                    'work_date' => $date->format('Y-m-d')
                ])->create();
            }
        }
    }
}
