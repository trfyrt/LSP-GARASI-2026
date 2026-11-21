<?php

namespace App\Enums;

/**
 * Tipe garasi beserta awalan kode ID (S###, C###, E###).
 */
enum GarageType: string
{
    case Bike = 'BIKE';
    case Car = 'CAR';
    case ElectricCar = 'EV';

    /** Awalan huruf pada kode garasi. */
    public function prefix(): string
    {
        return match ($this) {
            self::Bike => 'S',
            self::Car => 'C',
            self::ElectricCar => 'E',
        };
    }

    /** Tipe kendaraan yang boleh menempati garasi ini. */
    public function allowedVehicleTypes(): array
    {
        return match ($this) {
            self::Bike => [VehicleType::Bike, VehicleType::Motorcycle],
            self::Car => [VehicleType::Car, VehicleType::ElectricCar],
            self::ElectricCar => [VehicleType::ElectricCar],
        };
    }
}
