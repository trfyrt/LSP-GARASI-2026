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
        $this->assertSame(10000, $this->calc->extensionFee(10000, 1));
        $this->assertSame(0, $this->calc->extensionFee(10000, 0));
    }

    /** @return array<string, array{int, int}> */
    public static function monthProvider(): array
    {
        return [
            'Jan' => [1, 30], 'Feb' => [2, 27], 'Mar' => [3, 30], 'Apr' => [4, 29],
            'Mei' => [5, 30], 'Jun' => [6, 29], 'Jul' => [7, 30], 'Agu' => [8, 30],
            'Sep' => [9, 29], 'Okt' => [10, 30], 'Nov' => [11, 29], 'Des' => [12, 30],
        ];
    }

    #[DataProvider('monthProvider')]
    public function test_batas_sewa_harian_setiap_bulan_tahun_2026(int $month, int $expected): void
    {
        $this->assertSame($expected, $this->calc->maxDailyDays(CarbonImmutable::create(2026, $month, 1)));
    }

    public function test_batas_sewa_harian_sama_di_awal_dan_akhir_bulan(): void
    {
        $this->assertSame(29, $this->calc->maxDailyDays(CarbonImmutable::parse('2026-11-01')));
        $this->assertSame(29, $this->calc->maxDailyDays(CarbonImmutable::parse('2026-11-30')));
    }

    public function test_durasi_bulanan_mengabaikan_input_hari(): void
    {
        $this->assertSame(30, $this->calc->durationDays(RentalType::Monthly, 7, CarbonImmutable::parse('2026-11-21')));
        $this->assertSame(31, $this->calc->durationDays(RentalType::Monthly, null, CarbonImmutable::parse('2026-12-01')));
    }

    public function test_tanggal_selesai_melewati_pergantian_bulan_dan_tahun(): void
    {
        $this->assertSame('2027-01-04', $this->calc->endDate(CarbonImmutable::parse('2026-12-20'), 16)->toDateString());
        $this->assertSame('2026-03-01', $this->calc->endDate(CarbonImmutable::parse('2026-02-20'), 10)->toDateString());
    }

    public function test_tanggal_selesai_tidak_mengubah_tanggal_mulai(): void
    {
        $start = CarbonImmutable::parse('2026-11-21');
        $this->calc->endDate($start, 5);

        $this->assertSame('2026-11-21', $start->toDateString());
    }

    public function test_biaya_harian_satu_hari_sama_dengan_harga_harian(): void
    {
        $this->assertSame(15000, $this->calc->rentFee(RentalType::Daily, 15000, 1));
    }

    public function test_sewa_bulanan_lebih_murah_dari_tiga_puluh_hari_harian(): void
    {
        $monthly = $this->calc->rentFee(RentalType::Monthly, 10000, 30);
        $daily = $this->calc->rentFee(RentalType::Daily, 10000, 30);

        $this->assertLessThan($daily, $monthly);
        $this->assertSame(50000, $daily - $monthly);
    }

    public function test_biaya_listrik_nol_bila_kapasitas_atau_harga_kosong(): void
    {
        $this->assertSame(0, $this->calc->chargingFee(GarageType::ElectricCar, VehicleType::ElectricCar, null, 3000));
        $this->assertSame(0, $this->calc->chargingFee(GarageType::ElectricCar, VehicleType::ElectricCar, 50.0, null));
    }

    public function test_biaya_listrik_tidak_berlaku_untuk_kendaraan_bukan_mobil_listrik_di_garasi_e(): void
    {
        $this->assertSame(0, $this->calc->chargingFee(GarageType::ElectricCar, VehicleType::Car, 50.0, 3000));
        $this->assertSame(0, $this->calc->chargingFee(GarageType::ElectricCar, VehicleType::Motorcycle, 50.0, 3000));
        $this->assertSame(0, $this->calc->chargingFee(GarageType::Bike, VehicleType::ElectricCar, 50.0, 3000));
    }

    public function test_biaya_listrik_dibulatkan_ke_rupiah_terdekat(): void
    {
        $this->assertSame(1001, $this->calc->chargingFee(GarageType::ElectricCar, VehicleType::ElectricCar, 0.3337, 3000));
        $this->assertSame(1000, $this->calc->chargingFee(GarageType::ElectricCar, VehicleType::ElectricCar, 0.3333, 3000));
    }

    public function test_total_sewa_mobil_listrik_di_garasi_e_contoh_lengkap(): void
    {
        $rent = $this->calc->rentFee(RentalType::Daily, 30000, 2);
        $charging = $this->calc->chargingFee(GarageType::ElectricCar, VehicleType::ElectricCar, 50.0, 3000);

        $this->assertSame(60000, $rent);
        $this->assertSame(150000, $charging);
        $this->assertSame(210000, $rent + $charging);
    }
}
