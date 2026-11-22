<?php

namespace Tests\Feature;

use App\Enums\GarageStatus;
use App\Enums\GarageType;
use App\Enums\InvoiceType;
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
 * Admin memperpanjang sewa garasi yang sedang digunakan.
 */
class RentalExtensionTest extends TestCase
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

    /** Sewa 5 hari (21-25 Nov) di garasi C001 berharga 10.000/hari. */
    private function activeRental(): Rental
    {
        $garage = Garage::create(['code' => 'C001', 'type' => GarageType::Car, 'daily_price' => 10000, 'status' => GarageStatus::Used]);
        $renter = Renter::factory()->create();
        $vehicle = $renter->vehicles()->create(['vehicle_type' => 'CAR', 'plate_number' => 'B1', 'brand_model' => 'Avanza']);
        $rental = Rental::create([
            'id_renter' => $renter->id, 'id_garage' => $garage->id, 'id_vehicle' => $vehicle->id, 'rental_type' => 'DAILY',
        ]);
        Invoice::create([
            'id_rental' => $rental->id, 'type' => InvoiceType::Initial, 'start_date' => '2026-11-21', 'end_date' => '2026-11-25',
            'duration_days' => 5, 'rent_fee' => 50000, 'charging_fee' => 0, 'total_fee' => 50000, 'payment_proof_path' => 'payment-proofs/a.png',
        ]);

        return $rental;
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_daftar_menampilkan_garasi_used_hari_ini(): void
    {
        $this->activeRental();
        Garage::create(['code' => 'C002', 'type' => GarageType::Car, 'daily_price' => 10000]);

        $response = $this->actingAs($this->admin())->get('/admin/extensions')->assertOk();

        $response->assertSee('C001');
        $response->assertDontSee('C002');
    }

    public function test_sewa_yang_sudah_berakhir_tidak_muncul_dan_garasi_dibebaskan(): void
    {
        $rental = $this->activeRental();
        Carbon::setTestNow('2026-11-26 08:00:00');

        $this->actingAs($this->admin())->get('/admin/extensions')->assertOk()->assertDontSee('C001');

        $this->assertSame(GarageStatus::Available, $rental->garage->fresh()->status);
    }

    public function test_perpanjangan_membuat_invoice_extension_dengan_biaya_selisih_hari(): void
    {
        $rental = $this->activeRental();

        $this->actingAs($this->admin())
            ->post("/admin/extensions/{$rental->id}", [
                'new_end_date' => '2026-11-28',
                'payment_proof' => UploadedFile::fake()->image('tambahan.png'),
            ])
            ->assertRedirect(route('admin.extensions.index'));

        $extension = Invoice::where('type', InvoiceType::Extension)->firstOrFail();
        $this->assertSame('2026-11-26', $extension->start_date->toDateString());
        $this->assertSame('2026-11-28', $extension->end_date->toDateString());
        $this->assertSame(3, $extension->duration_days);
        $this->assertSame(30000, $extension->total_fee); // 3 hari x 10.000
        Storage::disk('public')->assertExists($extension->payment_proof_path);

        $this->assertSame('2026-11-28', $rental->fresh()->load('invoices')->endDate()->toDateString());
        $this->assertSame(GarageStatus::Used, $rental->garage->fresh()->status);
    }

    public function test_tanggal_baru_harus_setelah_tanggal_selesai_saat_ini(): void
    {
        $rental = $this->activeRental();

        foreach (['2026-11-25', '2026-11-23'] as $date) {
            $this->actingAs($this->admin())
                ->post("/admin/extensions/{$rental->id}", [
                    'new_end_date' => $date,
                    'payment_proof' => UploadedFile::fake()->image('tambahan.png'),
                ])
                ->assertSessionHasErrors('new_end_date');
        }

        $this->assertSame(1, Invoice::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_bukti_pembayaran_tambahan_wajib(): void
    {
        $rental = $this->activeRental();

        $this->actingAs($this->admin())
            ->post("/admin/extensions/{$rental->id}", ['new_end_date' => '2026-11-28'])
            ->assertSessionHasErrors('payment_proof');

        $this->assertSame(1, Invoice::count());
    }

    public function test_form_perpanjangan_menampilkan_tanggal_selesai_saat_ini(): void
    {
        $rental = $this->activeRental();

        $this->actingAs($this->admin())
            ->get("/admin/extensions/{$rental->id}")
            ->assertOk()
            ->assertSee('25 November 2026');
    }

    public function test_hanya_admin_yang_dapat_memperpanjang(): void
    {
        $rental = $this->activeRental();

        $this->get('/admin/extensions')->assertRedirect(route('login'));

        $renterUser = $rental->renter->user;
        $this->actingAs($renterUser)->get('/admin/extensions')->assertForbidden();
        $this->actingAs($renterUser)->post("/admin/extensions/{$rental->id}", [
            'new_end_date' => '2026-11-28',
            'payment_proof' => UploadedFile::fake()->image('tambahan.png'),
        ])->assertForbidden();

        $this->assertSame(1, Invoice::count());
    }

    /** Pemesanan lain pada garasi yang sama untuk periode tertentu. */
    private function otherBooking(Garage $garage, string $start, string $end): Rental
    {
        $renter = Renter::factory()->create();
        $vehicle = $renter->vehicles()->create(['vehicle_type' => 'CAR', 'plate_number' => 'D 1 XX', 'brand_model' => 'Brio']);
        $rental = Rental::create([
            'id_renter' => $renter->id, 'id_garage' => $garage->id, 'id_vehicle' => $vehicle->id, 'rental_type' => 'DAILY',
        ]);
        Invoice::create([
            'id_rental' => $rental->id, 'type' => InvoiceType::Initial, 'start_date' => $start, 'end_date' => $end,
            'duration_days' => 3, 'rent_fee' => 30000, 'charging_fee' => 0, 'total_fee' => 30000, 'payment_proof_path' => 'payment-proofs/b.png',
        ]);

        return $rental;
    }

    public function test_perpanjangan_ditolak_jika_menabrak_pemesanan_berikutnya(): void
    {
        $rental = $this->activeRental(); // C001: 21-25 Nov
        $this->otherBooking($rental->garage, '2026-11-28', '2026-11-30');

        $this->actingAs($this->admin())
            ->post("/admin/extensions/{$rental->id}", [
                'new_end_date' => '2026-11-29',
                'payment_proof' => UploadedFile::fake()->image('tambahan.png'),
            ])
            ->assertSessionHasErrors(['new_end_date' => 'Garasi sudah dipesan pada sebagian tanggal tersebut. Perpanjangan maksimal sampai 27 November 2026.']);

        $this->assertSame(2, Invoice::count()); // hanya invoice awal kedua sewa
        $this->assertSame([], Storage::disk('public')->allFiles());

        $this->actingAs($this->admin())
            ->post("/admin/extensions/{$rental->id}", [
                'new_end_date' => '2026-11-27',
                'payment_proof' => UploadedFile::fake()->image('tambahan.png'),
            ])
            ->assertRedirect(route('admin.extensions.index'));
    }

    public function test_form_perpanjangan_menampilkan_batas_pemesanan_berikutnya(): void
    {
        $rental = $this->activeRental();
        $this->otherBooking($rental->garage, '2026-11-28', '2026-11-30');

        $this->actingAs($this->admin())
            ->get("/admin/extensions/{$rental->id}")
            ->assertOk()
            ->assertSee('maksimal sampai 27 November 2026')
            ->assertSee('max="2026-11-27"', false);
    }

    public function test_daftar_perpanjangan_admin_hanya_memuat_sewa_yang_mencakup_hari_ini(): void
    {
        $this->activeRental(); // C001 berjalan
        $this->otherBooking(Garage::create(['code' => 'C009', 'type' => GarageType::Car, 'daily_price' => 10000]), '2026-12-01', '2026-12-03');

        $this->actingAs($this->admin())->get('/admin/extensions')->assertOk()
            ->assertSee('C001')
            ->assertDontSee('C009');
    }
}
