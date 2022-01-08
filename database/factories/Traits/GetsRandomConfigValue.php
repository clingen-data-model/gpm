<?php

namespace Database\Factories\Traits;

/**
 *
 */
trait GetsRandomConfigValue
{
    public function getRandomConfigValue(string $configString)
    {
        return $this->faker->randomElement(config($configString));
    }
}
