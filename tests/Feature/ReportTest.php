<?php

namespace Tests\Feature;

use App\Enums\GarageType;
use App\Enums\InvoiceType;
use App\Models\Garage;
use App\Models\Invoice;
use App\Models\Rental;
use App\Models\Renter;
use App\Models\User;
use App\Services\RentalReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Laporan penyewaan: filter bulan, hari, tipe garasi, dan penyewa.
 */
class ReportTest extends TestCase
{
    use RefreshDatabase;

    private Renter $ani;

    private Renter $budi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Carbon::setTestNow('2026-11-21 10:00:00');

        $this->ani = Renter::factory()->create(['id_user' => User::factory()->create(['name' => 'Ani Wijaya'])->id]);
        $this->budi = Renter::factory()->create(['id_user' => User::factory()->create(['name' => 'Budi Santoso'])->id]);

        // Ani: mobil C001 sewa baru 10 Nov (100.000) + perpanjangan 15 Nov (40.000)
        $car = $this->rental($this->ani, GarageType::Car, 'C001', 'B1');
        $this->invoice($car, InvoiceType::Initial, '2026-11-10', 100000);
        $this->invoice($car, InvoiceType::Extension, '2026-11-15', 40000);

        // Budi: mobil listrik E001 sewa baru 10 Nov (200.000 = 150.000 sewa + 50.000 listrik)
        $ev = $this->rental($this->budi, GarageType::ElectricCar, 'E001', 'B2');
        $this->invoice($ev, InvoiceType::Initial, '2026-11-10', 150000, 50000);

        // Budi: sepeda S001 sewa baru 3 Okt (30.000)
        $bike = $this->rental($this->budi, GarageType::Bike, 'S001', null);
        $this->invoice($bike, InvoiceType::Initial, '2026-10-03', 30000);

        // Ani: sewa tahun lalu, tidak boleh masuk laporan 2026
        $old = $this->rental($this->ani, GarageType::Car, 'C002', 'B3');
        $this->invoice($old, InvoiceType::Initial, '2025-12-31', 999000);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function rental(Renter $renter, GarageType $type, string $code, ?string $plate): Rental
    {
        $garage = Garage::create(['code' => $code, 'type' => $type, 'daily_price' => 10000]);
        $vehicle = $renter->vehicles()->create([
            'vehicle_type' => $type === GarageType::Bike ? 'BIKE' : 'CAR', 'plate_number' => $plate, 'brand_model' => 'Merek '.$code,
        ]);

        return Rental::create([
            'id_renter' => $renter->id, 'id_garage' => $garage->id, 'id_vehicle' => $vehicle->id, 'rental_type' => 'DAILY',
        ]);
    }

    private function invoice(Rental $rental, InvoiceType $type, string $start, int $rent, int $charging = 0): void
    {
        Invoice::create([
            'id_rental' => $rental->id, 'type' => $type, 'start_date' => $start, 'end_date' => $start,
            'duration_days' => 1, 'rent_fee' => $rent, 'charging_fee' => $charging, 'total_fee' => $rent + $charging,
            'payment_proof_path' => 'payment-proofs/x.png',
        ]);
    }

    private function summary(int $year = 2026, ?int $month = null, ?GarageType $type = null, ?int $renter = null): array
    {
        return app(RentalReportService::class)->summarize($year, $month, $type, $renter);
    }

    public function test_laporan_per_bulan_dalam_satu_tahun(): void
    {
        $report = $this->summary();

        $this->assertCount(12, $report['periods']);
        $oktober = $report['periods'][9];
        $november = $report['periods'][10];

        $this->assertSame(30000, $oktober['total_fee']);
        $this->assertSame(1, $november['extension']);
        $this->assertSame(2, $november['new']);
        $this->assertSame(100000 + 40000 + 200000, $november['total_fee']);
        $this->assertSame(50000, $november['charging_fee']);

        $this->assertSame(30000 + 340000, $report['totals']['total_fee']); // 2025 tidak ikut
        $this->assertSame(3, $report['totals']['new']);
        $this->assertSame(1, $report['totals']['extension']);
    }

    public function test_tahun_lain_hanya_memuat_transaksi_tahun_itu(): void
    {
        $this->assertSame(999000, $this->summary(2025)['totals']['total_fee']);
        $this->assertSame(0, $this->summary(2024)['totals']['total_fee']);
    }

    public function test_laporan_per_hari_dalam_satu_bulan(): void
    {
        $report = $this->summary(2026, 11);

        $this->assertCount(30, $report['periods']); // November
        $this->assertSame(2, $report['periods'][9]['new']); // 10 Nov
        $this->assertSame(300000, $report['periods'][9]['total_fee']);
        $this->assertSame(1, $report['periods'][14]['extension']); // 15 Nov
        $this->assertSame(0, $report['periods'][0]['total_fee']);
        $this->assertSame(340000, $report['totals']['total_fee']); // Oktober tidak ikut
    }

    public function test_jumlah_hari_mengikuti_bulan(): void
    {
        $this->assertCount(28, $this->summary(2026, 2)['periods']);
        $this->assertCount(29, $this->summary(2028, 2)['periods']);
        $this->assertCount(31, $this->summary(2026, 12)['periods']);
    }

    public function test_filter_per_tipe_garasi(): void
    {
        $ev = $this->summary(type: GarageType::ElectricCar);
        $this->assertSame(200000, $ev['totals']['total_fee']);

        $car = $this->summary(type: GarageType::Car);
        $this->assertSame(140000, $car['totals']['total_fee']);

        $rows = collect($this->summary()['by_type'])->pluck('total_fee', 'label');
        $this->assertSame(30000, $rows['Garasi Sepeda']);
        $this->assertSame(140000, $rows['Garasi Mobil']);
        $this->assertSame(200000, $rows['Garasi Mobil Listrik']);
    }

    public function test_filter_per_penyewa(): void
    {
        $this->assertSame(140000, $this->summary(renter: $this->ani->id)['totals']['total_fee']);
        $this->assertSame(230000, $this->summary(renter: $this->budi->id)['totals']['total_fee']);

        $byRenter = $this->summary()['by_renter'];
        $this->assertSame('Budi Santoso', $byRenter[0]['label']); // pendapatan terbesar lebih dulu
        $this->assertSame(230000, $byRenter[0]['total_fee']);
        $this->assertSame('Ani Wijaya', $byRenter[1]['label']);
    }

    public function test_filter_digabung(): void
    {
        $report = $this->summary(2026, 11, GarageType::Car, $this->ani->id);

        $this->assertSame(140000, $report['totals']['total_fee']);
        $this->assertSame(1, $report['totals']['new']);
        $this->assertSame(1, $report['totals']['extension']);
        $this->assertSame(0, $this->summary(2026, 10, GarageType::Car, $this->ani->id)['totals']['total_fee']);
    }

    public function test_halaman_laporan_menampilkan_data_dan_filter(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/reports?year=2026&month=11&type=CAR&renter='.$this->ani->id)
            ->assertOk()
            ->assertSee('Per hari, November 2026')
            ->assertSee('Rp 140.000')
            ->assertSee('Ani Wijaya')
            ->assertSee('Merek C001')
            ->assertDontSee('Merek E001');

        $this->actingAs($admin)->get('/admin/reports')->assertOk()->assertSee('Per bulan, tahun 2026');
    }

    public function test_filter_tidak_valid_ditolak(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (['month=13', 'type=PLANE', 'renter=9999', 'year=abc'] as $query) {
            $this->actingAs($admin)->get('/admin/reports?'.$query)->assertSessionHasErrors();
        }
    }

    public function test_hanya_admin_yang_dapat_membuka_laporan(): void
    {
        $this->get('/admin/reports')->assertRedirect(route('login'));
        $this->actingAs($this->ani->user)->get('/admin/reports')->assertForbidden();
    }
}
