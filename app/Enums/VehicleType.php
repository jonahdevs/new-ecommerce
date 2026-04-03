<?php

namespace App\Enums;

enum VehicleType: string
{
    case Motorbike = 'motorbike';
    case Van = 'van';
    case Truck3T = 'truck_3t';
    case Truck5T = 'truck_5t';
    case Truck7T = 'truck_7t';
    case Truck10T = 'truck_10t';

    public function label(): string
    {
        return match ($this) {
            self::Motorbike => 'Motor Bike',
            self::Van => 'Van',
            self::Truck3T => '3T Truck',
            self::Truck5T => '5T Truck',
            self::Truck7T => '7T Truck',
            self::Truck10T => '10T Truck',
        };
    }
}
