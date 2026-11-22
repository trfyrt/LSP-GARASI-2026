<?php

namespace Tests\Unit;

use App\Enums\GarageType;
use App\Enums\RentalType;
use App\Enums\VehicleType;
use App\Exceptions\RentalException;
use App\Services\RentalPriceCalculator;
use App\Services\RentalValidator;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Aturan validasi sewa baru.
 */
class RentalValidatorTest extends TestCase
{
    private RentalValidator $validator;

    private CarbonImmutable $today;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new RentalValidator(new RentalPriceCalculator);
        $this->today = CarbonImmutable::parse('2026-11-21');
    }

    private function check(
        VehicleType $vehicle,
        ?string $plate,
        ?float $kwh,
        GarageType $garage,
        RentalType $rental = RentalType::Daily,
        ?int $days = 5,
    ): void {
        $this->validator->validate($vehicle, $plate, $kwh, $garage, $rental, $days, $this->today);
    }

    private function assertRejected(callable $call, string $messagePart): void
    {
        try {
            $call();
            $this->fail('RentalException tidak dilempar.');
        } catch (RentalException $e) {
            $this->assertStringContainsString($messagePart, $e->getMessage());
        }
    }

    public function test_kombinasi_valid_diterima(): void
    {
        $this->check(VehicleType::Bike, null, null, GarageType::Bike);
        $this->check(VehicleType::Motorcycle, 'B1234XY', null, GarageType::Bike);
        $this->check(VehicleType::Car, 'B1234XY', null, GarageType::Car);
        $this->check(VehicleType::ElectricCar, 'B1234XY', 50, GarageType::Car);
        $this->check(VehicleType::ElectricCar, 'B1234XY', 50, GarageType::ElectricCar, RentalType::Monthly, null);

        $this->addToAssertionCount(1);
    }

    public function test_plat_wajib_kecuali_sepeda(): void
    {
        $this->assertRejected(fn () => $this->check(VehicleType::Motorcycle, null, null, GarageType::Bike), 'plat');
        $this->assertRejected(fn () => $this->check(VehicleType::Car, '  ', null, GarageType::Car), 'plat');
    }

    public function test_kapasitas_kwh_wajib_untuk_mobil_listrik(): void
    {
        $this->assertRejected(fn () => $this->check(VehicleType::ElectricCar, 'B1', null, GarageType::ElectricCar), 'kWh');
        $this->assertRejected(fn () => $this->check(VehicleType::ElectricCar, 'B1', 0, GarageType::Car), 'kWh');
    }

    public function test_kompatibilitas_kendaraan_dan_garasi(): void
    {
        $this->assertRejected(fn () => $this->check(VehicleType::Car, 'B1', null, GarageType::Bike), 'tidak dapat');
        $this->assertRejected(fn () => $this->check(VehicleType::Bike, null, null, GarageType::Car), 'tidak dapat');
        $this->assertRejected(fn () => $this->check(VehicleType::Car, 'B1', null, GarageType::ElectricCar), 'tidak dapat');
        $this->assertRejected(fn () => $this->check(VehicleType::Motorcycle, 'B1', null, GarageType::ElectricCar), 'tidak dapat');
    }

    public function test_batas_hari_sewa_harian_november_maksimal_29(): void
    {
        $this->check(VehicleType::Bike, null, null, GarageType::Bike, RentalType::Daily, 29);

        $this->assertRejected(fn () => $this->check(VehicleType::Bike, null, null, GarageType::Bike, RentalType::Daily, 30), '1 sampai 29');
        $this->assertRejected(fn () => $this->check(VehicleType::Bike, null, null, GarageType::Bike, RentalType::Daily, 0), '1 sampai 29');
        $this->assertRejected(fn () => $this->check(VehicleType::Bike, null, null, GarageType::Bike, RentalType::Daily, null), '1 sampai 29');
    }

    public function test_sewa_bulanan_tidak_dibatasi_jumlah_hari_input(): void
    {
        $this->check(VehicleType::Car, 'B1', null, GarageType::Car, RentalType::Monthly, null);
        $this->check(VehicleType::Car, 'B1', null, GarageType::Car, RentalType::Monthly, 99);

        $this->addToAssertionCount(1);
    }

    public function test_batas_bawah_sewa_harian_satu_hari(): void
    {
        $this->check(VehicleType::Bike, null, null, GarageType::Bike, RentalType::Daily, 1);
        $this->assertRejected(fn () => $this->check(VehicleType::Bike, null, null, GarageType::Bike, RentalType::Daily, -3), '1 sampai 29');
    }

    public function test_sepeda_tetap_diterima_walau_plat_diisi(): void
    {
        $this->check(VehicleType::Bike, 'B1', null, GarageType::Bike);

        $this->addToAssertionCount(1);
    }

    public function test_kapasitas_kwh_negatif_ditolak(): void
    {
        $this->assertRejected(fn () => $this->check(VehicleType::ElectricCar, 'B1', -5, GarageType::ElectricCar), 'kWh');
    }

    public function test_kwh_tidak_diwajibkan_untuk_mobil_bensin(): void
    {
        $this->check(VehicleType::Car, 'B1', null, GarageType::Car);

        $this->addToAssertionCount(1);
    }

    public function test_plat_dicek_sebelum_kompatibilitas(): void
    {
        // Motor tanpa plat di garasi mobil: kesalahan plat yang dilaporkan lebih dulu.
        $this->assertRejected(fn () => $this->check(VehicleType::Motorcycle, null, null, GarageType::Car), 'plat');
    }

    public function test_pesan_kompatibilitas_menyebut_nama_garasi(): void
    {
        $this->assertRejected(fn () => $this->check(VehicleType::Car, 'B1', null, GarageType::Bike), 'Garasi Sepeda');
        $this->assertRejected(fn () => $this->check(VehicleType::Car, 'B1', null, GarageType::ElectricCar), 'Garasi Mobil Listrik');
    }

    public function test_batas_hari_bulan_31_hari_maksimal_30(): void
    {
        $this->today = CarbonImmutable::parse('2026-01-15');

        $this->check(VehicleType::Bike, null, null, GarageType::Bike, RentalType::Daily, 30);
        $this->assertRejected(fn () => $this->check(VehicleType::Bike, null, null, GarageType::Bike, RentalType::Daily, 31), '1 sampai 30');
    }

    public function test_batas_hari_februari_maksimal_27(): void
    {
        $this->today = CarbonImmutable::parse('2026-02-10');

        $this->check(VehicleType::Bike, null, null, GarageType::Bike, RentalType::Daily, 27);
        $this->assertRejected(fn () => $this->check(VehicleType::Bike, null, null, GarageType::Bike, RentalType::Daily, 28), '1 sampai 27');
    }

    public function test_tanggal_mulai_hari_ini_dan_besok_diterima(): void
    {
        $this->validator->validateStartDate($this->today, $this->today);
        $this->validator->validateStartDate($this->today->addDay(), $this->today);

        $this->addToAssertionCount(1);
    }

    public function test_tanggal_mulai_kemarin_ditolak(): void
    {
        $this->assertRejected(fn () => $this->validator->validateStartDate($this->today->subDay(), $this->today), 'sebelum hari ini');
        $this->assertRejected(fn () => $this->validator->validateStartDate($this->today->subYear(), $this->today), 'sebelum hari ini');
    }

    public function test_batas_pemesanan_di_muka_enam_puluh_hari(): void
    {
        $this->validator->validateStartDate($this->today->addDays(RentalValidator::MAX_ADVANCE_DAYS), $this->today);
        $this->assertRejected(
            fn () => $this->validator->validateStartDate($this->today->addDays(RentalValidator::MAX_ADVANCE_DAYS + 1), $this->today),
            'paling jauh 60 hari',
        );
    }

    public function test_jam_pada_tanggal_mulai_diabaikan(): void
    {
        $this->validator->validateStartDate($this->today->setTime(23, 59), $this->today->setTime(8, 0));

        $this->addToAssertionCount(1);
    }

    public function test_batas_hari_harian_memakai_bulan_tanggal_mulai_bukan_hari_ini(): void
    {
        // Hari ini November (maks 29), tetapi tanggal mulai Desember (maks 30).
        $december = CarbonImmutable::parse('2026-12-01');

        $this->validator->validate(VehicleType::Bike, null, null, GarageType::Bike, RentalType::Daily, 30, $december);
        $this->assertRejected(
            fn () => $this->validator->validate(VehicleType::Bike, null, null, GarageType::Bike, RentalType::Daily, 30, $this->today),
            '1 sampai 29',
        );
    }
}
