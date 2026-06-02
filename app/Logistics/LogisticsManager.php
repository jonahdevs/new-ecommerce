<?php

namespace App\Logistics;

use App\Enums\CarrierDriver;
use App\Logistics\Contracts\LogisticsDriver;
use App\Logistics\Drivers\SelfManagedDriver;
use App\Models\ShippingCarrier;

class LogisticsManager
{
    public function driverForCarrier(ShippingCarrier $carrier): LogisticsDriver
    {
        if ($carrier->driver === CarrierDriver::SELF_MANAGED) {
            return new SelfManagedDriver;
        }

        $class = $carrier->driver->driverClass();

        return new $class($carrier);
    }
}
