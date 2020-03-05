<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Carbon\Carbon;
use Faker\Generator as Faker;
use GradziAu\Proda\Device;

$factory->define(Device::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
        'organisation_id' => $faker->word,
        'client_id' => $faker->word,
        'key_status' => Device::PRODA_DEVICE_ACTIVE,
        'key_expiry' => Carbon::now()->addDays(10),
        'one_time_activation_code' => $faker->word,
        'device_expiry' => (string)Carbon::now()->addDays(60),
    ];
});
