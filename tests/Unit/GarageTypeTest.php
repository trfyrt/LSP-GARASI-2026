<?php

namespace Tests\Unit;

use App\Enums\GarageStatus;
use App\Enums\GarageType;
use App\Enums\VehicleType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Aturan pada enum tipe garasi: kode, label, harga listrik, dan kompatibilitas kendaraan.
 */
class GarageTypeTest extends TestCase
{
    /** @return array<string, array{GarageType, string}> */
    public static function prefixProvider(): array
    {
        return [
            'sepeda' => [GarageType::Bike, 'S'],
            'mobil' => [GarageType::Car, 'C'],
            'mobil listrik' => [GarageType::ElectricCar, 'E'],
        ];
    }

    #[DataProvider('prefixProvider')]
    public function test_awalan_kode_sesuai_tipe(GarageType $type, string $prefix): void
    {
        $this->assertSame($prefix, $type->prefix());
    }

    public function test_label_tampilan_setiap_tipe(): void
    {
        $this->assertSame('Garasi Sepeda', GarageType::Bike->label());
        $this->assertSame('Garasi Mobil', GarageType::Car->label());
        $this->assertSame('Garasi Mobil Listrik', GarageType::ElectricCar->label());
    }

    public function test_harga_isi_listrik_hanya_untuk_garasi_mobil_listrik(): void
    {
        $this->assertFalse(GarageType::Bike->requiresChargingPrice());
        $this->assertFalse(GarageType::Car->requiresChargingPrice());
        $this->assertTrue(GarageType::ElectricCar->requiresChargingPrice());
    }

    /** @return array<string, array{GarageType, VehicleType, bool}> */
    public static function compatibilityProvider(): array
    {
        return [
            'sepeda di S' => [GarageType::Bike, VehicleType::Bike, true],
            'motor di S' => [GarageType::Bike, VehicleType::Motorcycle, true],
            'mobil di S' => [GarageType::Bike, VehicleType::Car, false],
            'mobil listrik di S' => [GarageType::Bike, VehicleType::ElectricCar, false],
            'sepeda di C' => [GarageType::Car, VehicleType::Bike, false],
            'motor di C' => [GarageType::Car, VehicleType::Motorcycle, false],
            'mobil di C' => [GarageType::Car, VehicleType::Car, true],
            'mobil listrik di C' => [GarageType::Car, VehicleType::ElectricCar, true],
            'sepeda di E' => [GarageType::ElectricCar, VehicleType::Bike, false],
            'motor di E' => [GarageType::ElectricCar, VehicleType::Motorcycle, false],
            'mobil bensin di E' => [GarageType::ElectricCar, VehicleType::Car, false],
            'mobil listrik di E' => [GarageType::ElectricCar, VehicleType::ElectricCar, true],
        ];
    }

    #[DataProvider('compatibilityProvider')]
    public function test_kompatibilitas_kendaraan_dan_garasi(GarageType $garage, VehicleType $vehicle, bool $allowed): void
    {
        $this->assertSame($allowed, in_array($vehicle, $garage->allowedVehicleTypes(), true));
    }

    public function test_label_status_garasi(): void
    {
        $this->assertSame('Tersedia', GarageStatus::Available->label());
        $this->assertSame('Digunakan', GarageStatus::Used->label());
    }

    public function test_nilai_enum_sesuai_kolom_database(): void
    {
        $this->assertSame('BIKE', GarageType::Bike->value);
        $this->assertSame('CAR', GarageType::Car->value);
        $this->assertSame('EV', GarageType::ElectricCar->value);
        $this->assertSame('AVAILABLE', GarageStatus::Available->value);
        $this->assertSame('USED', GarageStatus::Used->value);
    }
}
