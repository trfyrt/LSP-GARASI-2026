<?php

namespace Tests\Unit;

use App\Enums\VehicleType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Aturan pada enum tipe kendaraan: kewajiban plat dan kapasitas listrik.
 */
class VehicleTypeTest extends TestCase
{
    /** @return array<string, array{VehicleType, bool, bool}> */
    public static function requirementProvider(): array
    {
        return [
            'sepeda' => [VehicleType::Bike, false, false],
            'sepeda motor' => [VehicleType::Motorcycle, true, false],
            'mobil bensin' => [VehicleType::Car, true, false],
            'mobil listrik' => [VehicleType::ElectricCar, true, true],
        ];
    }

    #[DataProvider('requirementProvider')]
    public function test_kewajiban_plat_dan_kapasitas_baterai(VehicleType $type, bool $plate, bool $battery): void
    {
        $this->assertSame($plate, $type->requiresPlate());
        $this->assertSame($battery, $type->requiresBattery());
    }

    public function test_empat_tipe_kendaraan_tersedia(): void
    {
        $this->assertCount(4, VehicleType::cases());
        $this->assertSame(VehicleType::ElectricCar, VehicleType::from('EV'));
        $this->assertNull(VehicleType::tryFrom('PESAWAT'));
    }
}
