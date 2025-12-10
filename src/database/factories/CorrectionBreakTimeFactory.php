<?php

namespace Database\Factories;

use App\Models\CorrectionBreakTime;
use App\Models\CorrectionRequest;
use Illuminate\Database\Eloquent\Factories\Factory;


class CorrectionBreakTimeFactory extends Factory
{
    protected $model = CorrectionBreakTime::class;
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'correction_request_id' => CorrectionRequest::factory(),
            'new_break_in'  => $this->faker->time(),
            'new_break_out' => $this->faker->time(),
        ];
    }
}
