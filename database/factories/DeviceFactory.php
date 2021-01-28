<?php

namespace Database\Factories;

use Carbon\Carbon;
use GradziAu\Proda\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'organisation_id' => $this->faker->word,
            'client_id' => $this->faker->word,
            'key_status' => Device::PRODA_DEVICE_ACTIVE,
            'key_expiry' => Carbon::now()->addDays(10),
            'one_time_activation_code' => $this->faker->word,
            'device_expiry' => (string)Carbon::now()->addDays(60),
        ];
    }
}
