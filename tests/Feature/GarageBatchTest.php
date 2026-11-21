<?php

namespace Tests\Feature;

use App\Enums\GarageStatus;
use App\Enums\GarageType;
use App\Models\Garage;
use App\Models\Renter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin membuat garasi batch dengan kode otomatis per tipe.
 */
class GarageBatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'BIKE',
            'count' => 3,
            'daily_price' => 10000,
        ], $overrides);
    }

    /** @return list<string> */
    private function codes(GarageType $type): array
    {
        return Garage::where('type', $type)->orderBy('code')->pluck('code')->all();
    }

    public function test_tamu_dan_penyewa_tidak_boleh_mengakses(): void
    {
        $this->get('/admin/garages')->assertRedirect(route('login'));
        $this->post('/admin/garages', $this->payload())->assertRedirect(route('login'));

        $renter = Renter::factory()->create();
        $this->actingAs($renter->user)->get('/admin/garages')->assertForbidden();
        $this->actingAs($renter->user)->post('/admin/garages', $this->payload())->assertForbidden();

        $this->assertDatabaseCount('garage', 0);
    }

    public function test_admin_membuat_garasi_sepeda_dengan_kode_berurutan(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/garages', $this->payload())
            ->assertRedirect(route('admin.garages.index'))
            ->assertSessionHas('status');

        $this->assertSame(['S001', 'S002', 'S003'], $this->codes(GarageType::Bike));

        $garage = Garage::where('code', 'S001')->first();
        $this->assertSame(10000, $garage->daily_price);
        $this->assertSame(GarageStatus::Available, $garage->status);
        $this->assertNull($garage->charging_price);
    }

    public function test_batch_berikutnya_melanjutkan_dari_nomor_terakhir(): void
    {
        $admin = $this->actingAs($this->admin());

        $admin->post('/admin/garages', $this->payload(['count' => 2]));
        $admin->post('/admin/garages', $this->payload(['count' => 3]));

        $this->assertSame(['S001', 'S002', 'S003', 'S004', 'S005'], $this->codes(GarageType::Bike));
    }

    public function test_nomor_urut_terpisah_per_tipe(): void
    {
        $admin = $this->actingAs($this->admin());

        $admin->post('/admin/garages', $this->payload(['type' => 'BIKE', 'count' => 2]));
        $admin->post('/admin/garages', $this->payload(['type' => 'CAR', 'count' => 2, 'daily_price' => 50000]));
        $admin->post('/admin/garages', $this->payload(['type' => 'EV', 'count' => 1, 'daily_price' => 80000, 'charging_price' => 2500]));

        $this->assertSame(['S001', 'S002'], $this->codes(GarageType::Bike));
        $this->assertSame(['C001', 'C002'], $this->codes(GarageType::Car));
        $this->assertSame(['E001'], $this->codes(GarageType::ElectricCar));
    }

    public function test_garasi_listrik_menyimpan_harga_isi_listrik(): void
    {
        $this->actingAs($this->admin())->post('/admin/garages', $this->payload([
            'type' => 'EV', 'count' => 2, 'daily_price' => 80000, 'charging_price' => 2500,
        ]));

        $this->assertSame(2500, Garage::where('code', 'E001')->value('charging_price'));
        $this->assertSame(2500, Garage::where('code', 'E002')->value('charging_price'));
    }

    public function test_harga_isi_listrik_wajib_untuk_mobil_listrik(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/garages', $this->payload(['type' => 'EV']))
            ->assertSessionHasErrors('charging_price');

        $this->assertDatabaseCount('garage', 0);
    }

    public function test_harga_isi_listrik_diabaikan_untuk_tipe_selain_mobil_listrik(): void
    {
        $this->actingAs($this->admin())->post('/admin/garages', $this->payload([
            'type' => 'CAR', 'count' => 1, 'charging_price' => 9999,
        ]));

        $this->assertNull(Garage::where('code', 'C001')->value('charging_price'));
    }

    public function test_input_tidak_valid_ditolak(): void
    {
        $admin = $this->actingAs($this->admin());

        $admin->post('/admin/garages', $this->payload(['type' => 'TRUK']))->assertSessionHasErrors('type');
        $admin->post('/admin/garages', $this->payload(['count' => 0]))->assertSessionHasErrors('count');
        $admin->post('/admin/garages', $this->payload(['count' => 1000]))->assertSessionHasErrors('count');
        $admin->post('/admin/garages', $this->payload(['count' => 'abc']))->assertSessionHasErrors('count');
        $admin->post('/admin/garages', $this->payload(['daily_price' => 0]))->assertSessionHasErrors('daily_price');
        $admin->post('/admin/garages', $this->payload(['daily_price' => '']))->assertSessionHasErrors('daily_price');

        $this->assertDatabaseCount('garage', 0);
    }

    public function test_batas_999_per_tipe_dijaga_lintas_batch(): void
    {
        $admin = $this->actingAs($this->admin());

        $admin->post('/admin/garages', $this->payload(['count' => 999]))->assertSessionHasNoErrors();
        $this->assertSame(999, Garage::where('type', GarageType::Bike)->count());
        $this->assertSame('S999', Garage::where('type', GarageType::Bike)->max('code'));

        $admin->post('/admin/garages', $this->payload(['count' => 1]))->assertSessionHasErrors('count');
        $this->assertSame(999, Garage::where('type', GarageType::Bike)->count());

        // Tipe lain tetap punya slot sendiri.
        $admin->post('/admin/garages', $this->payload(['type' => 'CAR', 'count' => 1]))->assertSessionHasNoErrors();
        $this->assertSame(['C001'], $this->codes(GarageType::Car));
    }

    public function test_sisa_slot_dihitung_dari_kode_terakhir(): void
    {
        $admin = $this->actingAs($this->admin());
        $admin->post('/admin/garages', $this->payload(['count' => 990]));

        $admin->post('/admin/garages', $this->payload(['count' => 10]))->assertSessionHasErrors('count');
        $admin->post('/admin/garages', $this->payload(['count' => 9]))->assertSessionHasNoErrors();

        $this->assertSame('S999', Garage::where('type', GarageType::Bike)->max('code'));
    }

    public function test_halaman_daftar_menampilkan_garasi_dan_filter_tipe(): void
    {
        $admin = $this->actingAs($this->admin());
        $admin->post('/admin/garages', $this->payload(['count' => 2]));
        $admin->post('/admin/garages', $this->payload(['type' => 'CAR', 'count' => 1, 'daily_price' => 50000]));

        // Kode diperiksa pada sel tabel, karena teks penjelasan halaman juga menyebut contoh kode.
        $admin->get('/admin/garages')
            ->assertOk()
            ->assertSee('>S001</td>', false)
            ->assertSee('>C001</td>', false);

        $admin->get('/admin/garages?type=CAR')
            ->assertOk()
            ->assertSee('>C001</td>', false)
            ->assertDontSee('>S001</td>', false);
    }
}
