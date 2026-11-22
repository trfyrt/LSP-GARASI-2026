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

    /** Buat sewa langsung ke database untuk periode tertentu (tanpa melalui form). */
    private function booking(Garage $garage, string $start, string $end, string $plate = 'B 9 ZZ'): Rental
    {
        $renter = Renter::factory()->create();
        $vehicle = $renter->vehicles()->create(['vehicle_type' => 'CAR', 'plate_number' => $plate, 'brand_model' => 'Avanza']);
        $rental = Rental::create([
            'id_renter' => $renter->id, 'id_garage' => $garage->id, 'id_vehicle' => $vehicle->id, 'rental_type' => 'DAILY',
        ]);
        Invoice::create([
            'id_rental' => $rental->id, 'type' => 'INITIAL', 'start_date' => $start, 'end_date' => $end,
            'duration_days' => 5, 'rent_fee' => 50000, 'charging_fee' => 0, 'total_fee' => 50000, 'payment_proof_path' => 'x.png',
        ]);

        return $rental;
    }

    public function test_form_sewa_memuat_tanggal_mulai_hari_ini(): void
    {
        $renter = Renter::factory()->create();

        $this->actingAs($renter->user)
            ->get('/rentals/create')
            ->assertOk()
            ->assertSee('value="2026-11-21"', false)
            ->assertSee('min="2026-11-21"', false)
            ->assertSee('max="2027-01-20"', false);
    }

    public function test_pemesanan_di_muka_menyimpan_tanggal_mulai_dan_garasi_belum_digunakan_hari_ini(): void
    {
        $garage = $this->garage(GarageType::Car, 'C001');
        $renter = Renter::factory()->create();

        $this->actingAs($renter->user)
            ->post('/rentals', $this->payload($garage, ['start_date' => '2026-11-28', 'days' => 3]))
            ->assertRedirect(route('rentals.index'));

        $invoice = Invoice::firstOrFail();
        $this->assertSame('2026-11-28', $invoice->start_date->toDateString());
        $this->assertSame('2026-11-30', $invoice->end_date->toDateString());
        $this->assertSame(30000, $invoice->total_fee);
        $this->assertSame(GarageStatus::Available, $garage->fresh()->status); // belum dipakai hari ini
    }

    public function test_tanggal_mulai_kemarin_ditolak(): void
    {
        $garage = $this->garage(GarageType::Car, 'C001');
        $renter = Renter::factory()->create();

        $this->actingAs($renter->user)
            ->post('/rentals', $this->payload($garage, ['start_date' => '2026-11-20']))
            ->assertSessionHasErrors('rental');

        $this->assertDatabaseCount('rental', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_batas_pemesanan_di_muka_enam_puluh_hari(): void
    {
        $garage = $this->garage(GarageType::Car, 'C001');
        $renter = Renter::factory()->create();

        $this->actingAs($renter->user)
            ->post('/rentals', $this->payload($garage, ['start_date' => '2027-01-21']))
            ->assertSessionHasErrors('rental');

        $this->actingAs($renter->user)
            ->post('/rentals', $this->payload($garage, ['start_date' => '2027-01-20']))
            ->assertRedirect(route('rentals.index'));
    }

    public function test_format_tanggal_mulai_tidak_valid_ditolak(): void
    {
        $garage = $this->garage(GarageType::Car, 'C001');
        $renter = Renter::factory()->create();

        $this->actingAs($renter->user)
            ->post('/rentals', $this->payload($garage, ['start_date' => '21-11-2026']))
            ->assertSessionHasErrors('start_date');
    }

    public function test_garasi_yang_dipesan_di_muka_masih_dapat_dipakai_sebelum_dan_sesudah_periodenya(): void
    {
        $garage = $this->garage(GarageType::Car, 'C001');
        $this->booking($garage, '2026-12-01', '2026-12-03'); // pesanan lain

        $a = Renter::factory()->create();
        $b = Renter::factory()->create();
        $c = Renter::factory()->create();

        // Sebelum periode (21-25 Nov): bebas
        $this->actingAs($a->user)->post('/rentals', $this->payload($garage, ['plate_number' => 'B 1 AA']))
            ->assertRedirect(route('rentals.index'));

        // Mulai 28 Nov selama 5 hari (sampai 2 Des): menabrak periode 1-3 Des
        $this->actingAs($b->user)->post('/rentals', $this->payload($garage, ['start_date' => '2026-11-28', 'plate_number' => 'B 2 BB']))
            ->assertSessionHasErrors('rental');

        // Persis setelah periode (mulai 4 Des): bebas
        $this->actingAs($c->user)->post('/rentals', $this->payload($garage, ['start_date' => '2026-12-04', 'plate_number' => 'B 3 CC']))
            ->assertRedirect(route('rentals.index'));

        $this->assertDatabaseCount('rental', 3);
    }

    public function test_hari_terakhir_yang_beririsan_ditolak_dan_hari_berikutnya_diterima(): void
    {
        $garage = $this->garage(GarageType::Car, 'C001');
        $this->booking($garage, '2026-11-21', '2026-11-25');
        $renter = Renter::factory()->create();

        $this->actingAs($renter->user)->post('/rentals', $this->payload($garage, ['start_date' => '2026-11-25', 'days' => 2, 'plate_number' => 'B 1 AA']))
            ->assertSessionHasErrors('rental'); // 25 Nov masih terpakai

        $this->actingAs($renter->user)->post('/rentals', $this->payload($garage, ['start_date' => '2026-11-26', 'days' => 2, 'plate_number' => 'B 1 AA']))
            ->assertRedirect(route('rentals.index'));
    }

    public function test_batas_hari_sewa_harian_mengikuti_bulan_tanggal_mulai(): void
    {
        $garage = $this->garage(GarageType::Car, 'C001');
        $renter = Renter::factory()->create();

        // Hari ini (November) maksimal 29 hari.
        $this->actingAs($renter->user)->post('/rentals', $this->payload($garage, ['days' => 30]))
            ->assertSessionHasErrors('rental');

        // Mulai 1 Desember (31 hari) maksimal 30 hari.
        $this->actingAs($renter->user)->post('/rentals', $this->payload($garage, ['start_date' => '2026-12-01', 'days' => 30]))
            ->assertRedirect(route('rentals.index'));

        $this->assertSame('2026-12-30', Invoice::firstOrFail()->end_date->toDateString());
    }

    public function test_sewa_bulanan_di_muka_memakai_jumlah_hari_bulan_tanggal_mulai(): void
    {
        $garage = $this->garage(GarageType::Car, 'C001');
        $renter = Renter::factory()->create();

        $this->actingAs($renter->user)
            ->post('/rentals', $this->payload($garage, ['rental_type' => 'MONTHLY', 'days' => null, 'start_date' => '2026-12-01']))
            ->assertRedirect(route('rentals.index'));

        $invoice = Invoice::firstOrFail();
        $this->assertSame(31, $invoice->duration_days); // Desember
        $this->assertSame('2026-12-31', $invoice->end_date->toDateString());
        $this->assertSame(250000, $invoice->total_fee);
    }

    public function test_plat_yang_sama_boleh_dipesan_di_periode_yang_tidak_beririsan(): void
    {
        $a = $this->garage(GarageType::Car, 'C001');
        $b = $this->garage(GarageType::Car, 'C002');
        $renter = Renter::factory()->create();

        $this->actingAs($renter->user)->post('/rentals', $this->payload($a, ['plate_number' => 'B 1234 XY', 'days' => 3])) // 21-23 Nov
            ->assertRedirect(route('rentals.index'));

        // Periode beririsan: ditolak
        $this->actingAs($renter->user)->post('/rentals', $this->payload($b, ['plate_number' => 'b1234xy', 'start_date' => '2026-11-23', 'days' => 2]))
            ->assertSessionHasErrors('rental');

        // Periode setelahnya: diterima
        $this->actingAs($renter->user)->post('/rentals', $this->payload($b, ['plate_number' => 'b1234xy', 'start_date' => '2026-11-24', 'days' => 2]))
            ->assertRedirect(route('rentals.index'));
    }

    public function test_sewa_lanjutan_di_garasi_dan_kendaraan_yang_sama_dihitung_sebagai_rental_berbeda(): void
    {
        $garage = $this->garage(GarageType::Car, 'C001');
        $renter = Renter::factory()->create();

        // 36 hari di Desember = 30 hari + 6 hari, dipesan sebagai dua sewa terpisah.
        $this->actingAs($renter->user)
            ->post('/rentals', $this->payload($garage, ['start_date' => '2026-12-01', 'days' => 30, 'plate_number' => 'B 1234 XY']))
            ->assertRedirect(route('rentals.index'));
        $this->actingAs($renter->user)
            ->post('/rentals', $this->payload($garage, ['start_date' => '2026-12-31', 'days' => 6, 'plate_number' => 'B 1234 XY']))
            ->assertRedirect(route('rentals.index'));

        $this->assertSame(2, Rental::count());
        $this->assertSame(2, Invoice::count());
        $this->assertSame(2, Invoice::where('type', 'INITIAL')->count());
        $this->assertSame(0, Invoice::where('type', 'EXTENSION')->count());
        $this->assertSame(
            ['2026-12-30', '2027-01-05'],
            Invoice::orderBy('start_date')->get()->map(fn ($i) => $i->end_date->toDateString())->all(),
        );
    }

    public function test_penyewa_tidak_memiliki_fitur_perpanjangan_sendiri(): void
    {
        $garage = $this->garage(GarageType::Car, 'C001');
        $renter = Renter::factory()->create();
        $this->actingAs($renter->user)->post('/rentals', $this->payload($garage));
        $rental = Rental::firstOrFail();

        $this->actingAs($renter->user)->get("/rentals/{$rental->id}/extend")->assertNotFound();
        $this->actingAs($renter->user)->post("/rentals/{$rental->id}/extend", [
            'new_end_date' => '2026-11-27',
            'payment_proof' => UploadedFile::fake()->image('tambahan.png'),
        ])->assertNotFound();
        $this->actingAs($renter->user)->get('/rentals')->assertOk()->assertDontSee('Perpanjang');
    }

    public function test_status_garasi_used_hanya_jika_periode_mencakup_hari_ini(): void
    {
        $now = $this->garage(GarageType::Car, 'C001');
        $later = $this->garage(GarageType::Car, 'C002');
        $a = Renter::factory()->create();
        $b = Renter::factory()->create();

        $this->actingAs($a->user)->post('/rentals', $this->payload($now, ['plate_number' => 'B 1 AA']));
        $this->actingAs($b->user)->post('/rentals', $this->payload($later, ['plate_number' => 'B 2 BB', 'start_date' => '2026-12-05']));

        $this->assertSame(GarageStatus::Used, $now->fresh()->status);
        $this->assertSame(GarageStatus::Available, $later->fresh()->status);
    }

    public function test_endpoint_ketersediaan_menampilkan_garasi_yang_kosong_pada_periode_yang_diminta(): void
    {
        $this->garage(GarageType::Car, 'C001');
        $busy = $this->garage(GarageType::Car, 'C002');
        $this->booking($busy, '2026-11-21', '2026-11-25');
        $renter = Renter::factory()->create();

        // Hari ini 3 hari: C002 terpakai
        $codes = $this->actingAs($renter->user)
            ->getJson('/rentals/availability?rental_type=DAILY&days=3')
            ->assertOk()
            ->assertJsonPath('end_date', '2026-11-23')
            ->json('garages.*.code');
        $this->assertSame(['C001'], $codes);

        // Minggu depan: keduanya kosong
        $codes = $this->actingAs($renter->user)
            ->getJson('/rentals/availability?start_date=2026-11-28&rental_type=DAILY&days=3')
            ->assertOk()
            ->assertJsonPath('start_date', '2026-11-28')
            ->json('garages.*.code');
        $this->assertSame(['C001', 'C002'], $codes);

        // Periode yang menyentuh hari terakhir C002 (25 Nov): C002 tidak tersedia
        $codes = $this->actingAs($renter->user)
            ->getJson('/rentals/availability?start_date=2026-11-25&rental_type=DAILY&days=3')
            ->json('garages.*.code');
        $this->assertSame(['C001'], $codes);
    }

    public function test_endpoint_ketersediaan_sewa_bulanan_dan_pesan_kesalahan(): void
    {
        $this->garage(GarageType::Car, 'C001');
        $renter = Renter::factory()->create();

        $this->actingAs($renter->user)
            ->getJson('/rentals/availability?start_date=2026-12-01&rental_type=MONTHLY')
            ->assertOk()
            ->assertJsonPath('duration_days', 31)
            ->assertJsonPath('end_date', '2026-12-31');

        $this->actingAs($renter->user)
            ->getJson('/rentals/availability?start_date=2026-11-20&rental_type=DAILY&days=2')
            ->assertOk()
            ->assertJsonPath('garages', []);

        $this->actingAs($renter->user)
            ->getJson('/rentals/availability?rental_type=DAILY&days=30')
            ->assertOk()
            ->assertJsonPath('garages', []);
    }

    public function test_endpoint_ketersediaan_hanya_untuk_penyewa_terverifikasi(): void
    {
        $this->getJson('/rentals/availability')->assertUnauthorized();

        $pending = Renter::factory()->pending()->create();
        $this->actingAs($pending->user)->getJson('/rentals/availability')->assertForbidden();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->getJson('/rentals/availability')->assertForbidden();
    }

    public function test_sewa_saya_menampilkan_status_akan_datang_berjalan_dan_selesai(): void
    {
        $renter = Renter::factory()->create();
        $make = function (string $code, string $start, string $end) use ($renter): void {
            $garage = $this->garage(GarageType::Car, $code);
            $vehicle = $renter->vehicles()->create(['vehicle_type' => 'CAR', 'plate_number' => $code, 'brand_model' => 'Avanza']);
            $rental = Rental::create(['id_renter' => $renter->id, 'id_garage' => $garage->id, 'id_vehicle' => $vehicle->id, 'rental_type' => 'DAILY']);
            Invoice::create([
                'id_rental' => $rental->id, 'type' => 'INITIAL', 'start_date' => $start, 'end_date' => $end,
                'duration_days' => 3, 'rent_fee' => 30000, 'charging_fee' => 0, 'total_fee' => 30000, 'payment_proof_path' => 'x.png',
            ]);
        };
        $make('C001', '2026-11-10', '2026-11-12'); // selesai
        $make('C002', '2026-11-20', '2026-11-25'); // berjalan
        $make('C003', '2026-12-01', '2026-12-03'); // akan datang

        $this->actingAs($renter->user)->get('/rentals')->assertOk()
            ->assertSee('Selesai')->assertSee('Berjalan')->assertSee('Akan datang');
    }
}
