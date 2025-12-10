<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'remember_token' => Str::random(10),
            'admin_status' => 'general',
            'attendance_status' => 'before_work',
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function unverified()
    {
        return $this->state(function () {
            return [
                'email_verified_at' => null,
            ];
        });
    }

    public function admin()
    {
        return $this->state(fn() => [
            'admin_status' => 'admin',
        ]);
    }

    public function general()
    {
        return $this->state(fn() => [
            'admin_status' => 'general',
        ]);
    }

    public function beforeWork()
    {
        return $this->state(fn() => [
            'attendance_status' => 'before_work',
        ]);
    }

    public function working()
    {
        return $this->state(fn() => [
            'attendance_status' => 'working',
        ]);
    }

    public function onBreak()
    {
        return $this->state(fn() => [
            'attendance_status' => 'break',
        ]);
    }

    public function afterWork()
    {
        return $this->state(fn() => [
            'attendance_status' => 'after_work',
        ]);
    }
}
