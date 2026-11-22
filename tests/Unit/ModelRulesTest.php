<?php

namespace Tests\Unit;

use App\Enums\GarageStatus;
use App\Enums\GarageType;
use App\Enums\InvoiceType;
use App\Enums\RenterStatus;
use App\Enums\UserRole;
use App\Enums\VehicleType;
use App\Models\Garage;
use App\Models\Invoice;
use App\Models\Rental;
use App\Models\Renter;
use App\Models\User;
use App\Models\Vehicle;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Aturan sederhana pada model, diuji di memori tanpa database.
 */
class ModelRulesTest extends TestCase
{
    /** @return array<string, array{RenterStatus, bool}> */
    public static function statusProvider(): array
    {
        return [
            'pending' => [RenterStatus::Pending, false],
            'approved' => [RenterStatus::Approved, true],
            'rejected' => [RenterStatus::Rejected, false],
        ];
    }

    #[DataProvider('statusProvider')]
    public function test_hanya_penyewa_approved_yang_dapat_menyewa(RenterStatus $status, bool $expected): void
    {
        $renter = new Renter(['status' => $status]);

        $this->assertSame($expected, $renter->canRent());
    }

    public function test_penyewa_baru_berstatus_pending_secara_default(): void
    {
        $this->assertSame(RenterStatus::Pending, (new Renter)->status);
        $this->assertFalse((new Renter)->canRent());
    }

    #[DataProvider('statusProvider')]
    public function test_user_dapat_menyewa_mengikuti_status_profil_penyewa(RenterStatus $status, bool $expected): void
    {
        $user = new User(['role' => UserRole::Renter]);
        $user->setRelation('renter', new Renter(['status' => $status]));

        $this->assertSame($expected, $user->canRent());
    }

    public function test_admin_tanpa_profil_penyewa_tidak_dapat_menyewa(): void
    {
        $admin = new User(['role' => UserRole::Admin]);
        $admin->setRelation('renter', null);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->canRent());
    }

    public function test_penyewa_bukan_admin(): void
    {
        $this->assertFalse((new User(['role' => UserRole::Renter]))->isAdmin());
    }

    /** @return array<string, array{VehicleType, GarageType, bool}> */
    public static function parkingProvider(): array
    {
        return [
            'sepeda di S' => [VehicleType::Bike, GarageType::Bike, true],
            'motor di S' => [VehicleType::Motorcycle, GarageType::Bike, true],
            'mobil di S' => [VehicleType::Car, GarageType::Bike, false],
            'mobil di C' => [VehicleType::Car, GarageType::Car, true],
            'mobil di E' => [VehicleType::Car, GarageType::ElectricCar, false],
            'listrik di C' => [VehicleType::ElectricCar, GarageType::Car, true],
            'listrik di E' => [VehicleType::ElectricCar, GarageType::ElectricCar, true],
            'listrik di S' => [VehicleType::ElectricCar, GarageType::Bike, false],
        ];
    }

    #[DataProvider('parkingProvider')]
    public function test_kendaraan_dapat_menempati_garasi_yang_cocok(VehicleType $vehicle, GarageType $garage, bool $expected): void
    {
        $this->assertSame($expected, (new Vehicle(['vehicle_type' => $vehicle]))->canBeParkedIn($garage));
    }

    public function test_garasi_baru_tersedia_secara_default(): void
    {
        $garage = new Garage;

        $this->assertSame(GarageStatus::Available, $garage->status);
        $this->assertTrue($garage->isAvailable());
    }

    public function test_garasi_used_tidak_tersedia(): void
    {
        $this->assertFalse((new Garage(['status' => GarageStatus::Used]))->isAvailable());
    }

    private function invoice(InvoiceType $type, string $start, string $end, int $total): Invoice
    {
        return new Invoice([
            'type' => $type, 'start_date' => $start, 'end_date' => $end, 'total_fee' => $total,
        ]);
    }

    public function test_sewa_tanpa_perpanjangan_memakai_tanggal_dan_biaya_invoice_awal(): void
    {
        $rental = new Rental;
        $rental->setRelation('invoices', collect([$this->invoice(InvoiceType::Initial, '2026-11-21', '2026-11-25', 50000)]));

        $this->assertSame('2026-11-21', $rental->startDate()->toDateString());
        $this->assertSame('2026-11-25', $rental->endDate()->toDateString());
        $this->assertSame(50000, $rental->totalFee());
    }

    public function test_perpanjangan_memundurkan_tanggal_selesai_dan_menambah_total_biaya(): void
    {
        $rental = new Rental;
        $rental->setRelation('invoices', collect([
            $this->invoice(InvoiceType::Initial, '2026-11-21', '2026-11-25', 50000),
            $this->invoice(InvoiceType::Extension, '2026-11-26', '2026-11-28', 30000),
            $this->invoice(InvoiceType::Extension, '2026-11-29', '2026-12-01', 30000),
        ]));

        $this->assertSame('2026-11-21', $rental->startDate()->toDateString());
        $this->assertSame('2026-12-01', $rental->endDate()->toDateString());
        $this->assertSame(110000, $rental->totalFee());
    }

    public function test_tanggal_selesai_diambil_dari_invoice_terakhir_walau_urutan_acak(): void
    {
        $rental = new Rental;
        $rental->setRelation('invoices', collect([
            $this->invoice(InvoiceType::Extension, '2026-11-26', '2026-11-28', 30000),
            $this->invoice(InvoiceType::Initial, '2026-11-21', '2026-11-25', 50000),
        ]));

        $this->assertSame('2026-11-28', $rental->endDate()->toDateString());
        $this->assertSame('2026-11-21', $rental->startDate()->toDateString());
    }
}
