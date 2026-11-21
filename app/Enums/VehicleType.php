<?php

namespace App\Enums;

/**
 * Tipe kendaraan penyewa.
 */
enum VehicleType: string
{
    case Bike = 'BIKE';
    case Motorcycle = 'MOTORCYCLE';
    case Car = 'CAR';
    case ElectricCar = 'EV';

    /** Nomor plat wajib kecuali sepeda (Bike). */
    public function requiresPlate(): bool
    {
        return $this !== self::Bike;
    }

    /** Kapasitas baterai (kWh) hanya untuk mobil listrik. */
    public function requiresBattery(): bool
    {
        return $this === self::ElectricCar;
    }
}
