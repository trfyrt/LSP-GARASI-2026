<?php

namespace Tests\Unit;

use App\Enums\GarageType;
use App\Enums\RentalType;
use App\Enums\VehicleType;
use App\Services\RentalPriceCalculator;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Kalkulasi tanggal dan biaya sewa.
 */
class RentalPriceCalculatorTest extends TestCase
{
    private RentalPriceCalculator $calc;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calc = new RentalPriceCalculator;
    }

    /** @return array<string, array{string, int}> */
    public static function maxDailyProvider(): array
    {
        return [
            'November 30 hari' => ['2026-11-21', 29],
            'Februari 28 hari' => ['2026-02-10', 27],
            'Februari kabisat' => ['2028-02-10', 28],
            'Januari 31 hari' => ['2026-01-05', 30],
        ];
    }

    #[DataProvider('maxDailyProvider')]
    public function test_sewa_harian_maksimal_jumlah_hari_bulan_dikurangi_satu(string $date, int $expected): void
    {
        $this->assertSame($expected, $this->calc->maxDailyDays(CarbonImmutable::parse($date)));
    }

    public function test_sewa_bulanan_mengikuti_jumlah_hari_bulan_saat_disewa(): void
    {
        $this->assertSame(30, $this->calc->durationDays(RentalType::Monthly, null, CarbonImmutable::parse('2026-11-21')));
        $this->assertSame(28, $this->calc->durationDays(RentalType::Monthly, 5, CarbonImmutable::parse('2026-02-01')));
        $this->assertSame(7, $this->calc->durationDays(RentalType::Daily, 7, CarbonImmutable::parse('2026-11-21')));
    }

    public function test_tanggal_selesai_inklusif(): void
    {
        $start = CarbonImmutable::parse('2026-11-21');

        $this->assertSame('2026-11-21', $this->calc->endDate($start, 1)->toDateString());
        $this->assertSame('2026-11-27', $this->calc->endDate($start, 7)->toDateString());
        $this->assertSame('2026-12-20', $this->calc->endDate($start, 30)->toDateString());
    }

    public function test_biaya_harian_dan_bulanan(): void
    {
        $this->assertSame(70000, $this->calc->rentFee(RentalType::Daily, 10000, 7));
        // Bulanan = 25 x harga harian, berapa pun jumlah hari bulannya.
        $this->assertSame(250000, $this->calc->rentFee(RentalType::Monthly, 10000, 30));
        $this->assertSame(250000, $this->calc->rentFee(RentalType::Monthly, 10000, 28));
    }

    public function test_biaya_listrik_hanya_mobil_listrik_di_garasi_listrik(): void
    {
        $this->assertSame(
            600000,
            $this->calc->chargingFee(GarageType::ElectricCar, VehicleType::ElectricCar, 60.0, 10000),
        );
        $this->assertSame(
            1500,
            $this->calc->chargingFee(GarageType::ElectricCar, VehicleType::ElectricCar, 0.15, 10000),
        );
        $this->assertSame(
            0,
            $this->calc->chargingFee(GarageType::Car, VehicleType::ElectricCar, 60.0, 10000),
        );
        $this->assertSame(
            0,
            $this->calc->chargingFee(GarageType::Car, VehicleType::Car, null, null),
        );
    }

    public function test_biaya_perpanjangan_harga_harian_kali_selisih_hari(): void
    {
        $this->assertSame(30000, $this->calc->extensionFee(10000, 3));
    }
}
