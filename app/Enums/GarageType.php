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

    /** Nama tampilan tipe garasi. */
    public function label(): string
    {
        return match ($this) {
            self::Bike => 'Garasi Sepeda',
            self::Car => 'Garasi Mobil',
            self::ElectricCar => 'Garasi Mobil Listrik',
        };
    }

    /** Harga isi listrik per kWh hanya berlaku untuk garasi mobil listrik. */
    public function requiresChargingPrice(): bool
    {
        return $this === self::ElectricCar;
    }

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
