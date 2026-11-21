<?php

namespace App\Enums;

/**
 * Tipe kendaraan penyewa.
 */
enum VehicleType: string
{
    case Bicycle = 'bicycle';
    case Motorcycle = 'motorcycle';
    case Car = 'car';
    case ElectricCar = 'electric_car';

    /** Nomor plat wajib kecuali sepeda. */
    public function requiresPlate(): bool
    {
        return $this !== self::Bicycle;
    }

    /** Kapasitas baterai (kWh) hanya untuk mobil listrik. */
    public function requiresBattery(): bool
    {
        return $this === self::ElectricCar;
    }
}
