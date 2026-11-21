<?php

namespace Tests\Feature;

use App\Enums\GarageStatus;
use App\Enums\GarageType;
use App\Models\Garage;
use App\Models\Invoice;
use App\Models\Rental;
use App\Models\Renter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Penyewa menyewa garasi baru: filter, kalkulasi biaya, bukti bayar, dan status garasi.
 */
class RentalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('public');
        Carbon::setTestNow('2026-11-21 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function garage(GarageType $type, string $code, int $price = 10000, ?int $charging = null): Garage
    {
        return Garage::create([
            'code' => $code, 'type' => $type, 'daily_price' => $price, 'charging_price' => $charging,
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(Garage $garage, array $overrides = []): array
    {
        return array_merge([
            'vehicle_type' => 'CAR',
            'plate_number' => 'b 1234 xy',
            'brand_model' => 'Toyota Avanza',
            'rental_type' => 'DAILY',
            'days' => 5,
            'id_garage' => $garage->id,
            'payment_proof' => UploadedFile::fake()->image('bukti.png'),
        ], $overrides);
    }

    public function test_sewa_harian_menyimpan_invoice_dan_menandai_garasi_used(): void
    {
        $garage = $this->garage(GarageType::Car, 'C001');
        $renter = Renter::factory()->create();

        $this->actingAs($renter->user)
            ->post('/rentals', $this->payload($garage))
            ->assertRedirect(route('rentals.index'));

        $invoice = Invoice::firstOrFail();
        $this->assertSame('2026-11-21', $invoice->start_date->toDateString());
        $this->assertSame('2026-11-25', $invoice->end_date->toDateString());
        $this->assertSame(5, $invoice->duration_days);
        $this->assertSame(50000, $invoice->total_fee);
        $this->assertSame(0, $invoice->charging_fee);
        Storage::disk('public')->assertExists($invoice->payment_proof_path);

        $this->assertSame(GarageStatus::Used, $garage->fresh()->status);
        $this->assertDatabaseHas('vehicle', ['id_renter' => $renter->id, 'plate_number' => 'B 1234 XY']);
    }

    public function test_sewa_bulanan_dua_puluh_lima_kali_harga_harian(): void
    {
        $garage = $this->garage(GarageType::Car, 'C001');
        $renter = Renter::factory()->create();

        $this->actingAs($renter->user)
            ->post('/rentals', $this->payload($garage, ['rental_type' => 'MONTHLY', 'days' => null]))
            ->assertRedirect(route('rentals.index'));

        $invoice = Invoice::firstOrFail();
        $this->assertSame(30, $invoice->duration_days); // November
        $this->assertSame('2026-12-20', $invoice->end_date->toDateString());
        $this->assertSame(250000, $invoice->total_fee);
    }

    public function test_mobil_listrik_di_garasi_listrik_menambah_biaya_isi_listrik(): void
    {
        $garage = $this->garage(GarageType::ElectricCar, 'E001', 20000, 3000);
        $renter = Renter::factory()->create();

        $this->actingAs($renter->user)
            ->post('/rentals', $this->payload($garage, ['vehicle_type' => 'EV', 'battery_kwh' => 50, 'days' => 2]))
            ->assertRedirect(route('rentals.index'));

        $invoice = Invoice::firstOrFail();
        $this->assertSame(40000, $invoice->rent_fee);
        $this->assertSame(150000, $invoice->charging_fee); // 50 kWh x 3.000
        $this->assertSame(190000, $invoice->total_fee);
    }

    public function test_mobil_listrik_di_garasi_mobil_biasa_tanpa_biaya_listrik(): void
    {
        $garage = $this->garage(GarageType::Car, 'C001');
        $renter = Renter::factory()->create();

        $this->actingAs($renter->user)
            ->post('/rentals', $this->payload($garage, ['vehicle_type' => 'EV', 'battery_kwh' => 50, 'days' => 2]));

        $this->assertSame(0, Invoice::firstOrFail()->charging_fee);
    }

    public function test_sepeda_tidak_wajib_plat(): void
    {
        $garage = $this->garage(GarageType::Bike, 'S001', 5000);
        $renter = Renter::factory()->create();

        $this->actingAs($renter->user)
            ->post('/rentals', $this->payload($garage, ['vehicle_type' => 'BIKE', 'plate_number' => null]))
            ->assertRedirect(route('rentals.index'));

        $this->assertDatabaseHas('vehicle', ['vehicle_type' => 'BIKE', 'plate_number' => null]);
    }

    public function test_motor_wajib_plat(): void
    {
        $garage = $this->garage(GarageType::Bike, 'S001');
        $renter = Renter::factory()->create();

        $this->actingAs($renter->user)
            ->post('/rentals', $this->payload($garage, ['vehicle_type' => 'MOTORCYCLE', 'plate_number' => null]))
            ->assertSessionHasErrors('rental');

        $this->assertDatabaseCount('rental', 0);
        $this->assertSame(GarageStatus::Available, $garage->fresh()->status);
    }

    public function test_kendaraan_tidak_cocok_dengan_garasi_ditolak(): void
    {
        $garage = $this->garage(GarageType::Bike, 'S001');
        $renter = Renter::factory()->create();

        $this->actingAs($renter->user)
            ->post('/rentals', $this->payload($garage, ['vehicle_type' => 'CAR']))
            ->assertSessionHasErrors('rental');

        $this->assertDatabaseCount('invoice', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_sewa_harian_melebihi_batas_ditolak(): void
    {
        $garage = $this->garage(GarageType::Car, 'C001');
        $renter = Renter::factory()->create();

        $this->actingAs($renter->user)
            ->post('/rentals', $this->payload($garage, ['days' => 30]))
            ->assertSessionHasErrors('rental');

        $this->actingAs($renter->user)
            ->post('/rentals', $this->payload($garage, ['days' => 29]))
            ->assertRedirect(route('rentals.index'));
    }

    public function test_garasi_used_tidak_bisa_disewa_ulang(): void
    {
        $garage = $this->garage(GarageType::Car, 'C001');
        $first = Renter::factory()->create();
        $second = Renter::factory()->create();

        $this->actingAs($first->user)->post('/rentals', $this->payload($garage));

        $this->actingAs($second->user)
            ->post('/rentals', $this->payload($garage))
            ->assertSessionHasErrors('rental');

        $this->assertDatabaseCount('rental', 1);
    }

    public function test_kendaraan_yang_sama_tidak_bisa_menyewa_dua_garasi_sekaligus(): void
    {
        $a = $this->garage(GarageType::Car, 'C001');
        $b = $this->garage(GarageType::Car, 'C002');
        $first = Renter::factory()->create();
        $other = Renter::factory()->create();

        $this->actingAs($first->user)->post('/rentals', $this->payload($a, ['plate_number' => 'B 1234 XY', 'days' => 2]));

        // Penyewa sama maupun penyewa lain; format plat berbeda tetap dianggap kendaraan yang sama.
        foreach ([[$first, 'B1234XY'], [$other, 'b 1234 xy']] as [$renter, $plate]) {
            $this->actingAs($renter->user)
                ->post('/rentals', $this->payload($b, ['plate_number' => $plate]))
                ->assertSessionHasErrors('rental');
        }

        $this->assertDatabaseCount('rental', 1);
        $this->assertSame(GarageStatus::Available, $b->fresh()->status);

        // Setelah masa sewa pertama berakhir, kendaraan boleh menyewa lagi.
        Carbon::setTestNow('2026-11-23 08:00:00');
        $this->actingAs($first->user)
            ->post('/rentals', $this->payload($b, ['plate_number' => 'B1234XY']))
            ->assertRedirect(route('rentals.index'));
    }

    public function test_garasi_kembali_tersedia_setelah_masa_sewa_berakhir(): void
    {
        $garage = $this->garage(GarageType::Car, 'C001');
        $first = Renter::factory()->create();
        $second = Renter::factory()->create();

        $this->actingAs($first->user)->post('/rentals', $this->payload($garage, ['days' => 2])); // s.d. 22 Nov

        Carbon::setTestNow('2026-11-23 08:00:00');

        $this->actingAs($second->user)
            ->post('/rentals', $this->payload($garage))
            ->assertRedirect(route('rentals.index'));

        $this->assertDatabaseCount('rental', 2);
    }

    public function test_penyewa_dapat_menyewa_lebih_dari_satu_garasi(): void
    {
        $a = $this->garage(GarageType::Car, 'C001');
        $b = $this->garage(GarageType::Car, 'C002');
        $renter = Renter::factory()->create();

        $this->actingAs($renter->user)->post('/rentals', $this->payload($a));
        $this->actingAs($renter->user)->post('/rentals', $this->payload($b, ['plate_number' => 'D 99 ZZ']));

        $this->assertSame(2, $renter->rentals()->count());
    }

    public function test_penyewa_pending_atau_ditolak_tidak_dapat_menyewa(): void
    {
        $garage = $this->garage(GarageType::Car, 'C001');
        $pending = Renter::factory()->pending()->create();

        $this->actingAs($pending->user)->get('/rentals/create')->assertForbidden();
        $this->actingAs($pending->user)->post('/rentals', $this->payload($garage))->assertForbidden();

        $this->assertDatabaseCount('rental', 0);
        $this->assertSame(GarageStatus::Available, $garage->fresh()->status);
    }

    public function test_hanya_penyewa_yang_dapat_mengakses_halaman_sewa(): void
    {
        $this->get('/rentals/create')->assertRedirect(route('login'));

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/rentals')->assertForbidden();
        $this->actingAs($admin)->get('/rentals/create')->assertForbidden();
    }

    public function test_bukti_bayar_harus_gambar_berukuran_wajar(): void
    {
        $garage = $this->garage(GarageType::Car, 'C001');
        $renter = Renter::factory()->create();

        $this->actingAs($renter->user)
            ->post('/rentals', $this->payload($garage, ['payment_proof' => UploadedFile::fake()->create('bukti.pdf', 10, 'application/pdf')]))
            ->assertSessionHasErrors('payment_proof');

        $this->actingAs($renter->user)
            ->post('/rentals', $this->payload($garage, ['payment_proof' => UploadedFile::fake()->image('besar.png')->size(3000)]))
            ->assertSessionHasErrors('payment_proof');

        $this->actingAs($renter->user)
            ->post('/rentals', array_diff_key($this->payload($garage), ['payment_proof' => 1]))
            ->assertSessionHasErrors('payment_proof');

        $this->assertDatabaseCount('rental', 0);
    }

    public function test_form_menampilkan_hanya_garasi_tersedia(): void
    {
        $this->garage(GarageType::Car, 'C001');
        $used = $this->garage(GarageType::Car, 'C002');
        $used->update(['status' => GarageStatus::Used]);
        $renter = Renter::factory()->create();
        $vehicle = $renter->vehicles()->create(['vehicle_type' => 'CAR', 'plate_number' => 'B1', 'brand_model' => 'Avanza']);
        $rental = Rental::create([
            'id_renter' => $renter->id, 'id_garage' => $used->id, 'id_vehicle' => $vehicle->id, 'rental_type' => 'DAILY',
        ]);
        Invoice::create([
            'id_rental' => $rental->id, 'type' => 'INITIAL', 'start_date' => '2026-11-21', 'end_date' => '2026-11-25',
            'duration_days' => 5, 'rent_fee' => 50000, 'charging_fee' => 0, 'total_fee' => 50000, 'payment_proof_path' => 'x.png',
        ]);

        $response = $this->actingAs($renter->user)->get('/rentals/create')->assertOk();

        $response->assertSee('C001');
        $response->assertDontSee('C002');
    }
}
