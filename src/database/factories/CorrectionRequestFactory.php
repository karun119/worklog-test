<?php

namespace Database\Factories;

use App\Models\CorrectionRequest;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;


class CorrectionRequestFactory extends Factory
{
    protected $model = CorrectionRequest::class;
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'attendance_id' => Attendance::factory(), // OR null
            'user_id'       => User::factory(),
            'comment'       => $this->faker->sentence(),
            'new_date'      => $this->faker->date(),
            'new_clock_in'  => $this->faker->time(),
            'new_clock_out' => $this->faker->time(),
            'application_date' => now(),
            'status'        => 'pending',
            'created_by_admin' => false,
        ];
    }
}
