<?php

namespace Tests\Feature;

use App\Enums\RenterStatus;
use App\Models\Renter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin memverifikasi pendaftar baru (terima/tolak) dan pembatasan akses halaman.
 */
class RenterVerificationTest extends TestCase
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

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/admin/renters')->assertRedirect(route('login'));
    }

    public function test_penyewa_tidak_boleh_membuka_halaman_admin_atau_memverifikasi(): void
    {
        $pending = Renter::factory()->pending()->create();
        $other = Renter::factory()->create();

        $this->actingAs($other->user)->get('/admin/renters')->assertForbidden();
        $this->actingAs($other->user)->post("/admin/renters/{$pending->id}/approve")->assertForbidden();

        $this->assertSame(RenterStatus::Pending, $pending->fresh()->status);
    }

    public function test_admin_melihat_pendaftar_pending_beserta_nik(): void
    {
        $pending = Renter::factory()->pending()->create(['nik' => '3201010101010001']);
        $approved = Renter::factory()->create(['nik' => '3201010101010002']);

        $response = $this->actingAs($this->admin())->get('/admin/renters')->assertOk();

        $response->assertSee('3201010101010001')
            ->assertSee($pending->user->name)
            ->assertSee('Terverifikasi')
            ->assertSee($approved->user->email);
    }

    public function test_admin_dapat_menerima_pendaftar(): void
    {
        $renter = Renter::factory()->pending()->create();

        $this->actingAs($this->admin())
            ->post("/admin/renters/{$renter->id}/approve")
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(RenterStatus::Approved, $renter->fresh()->status);
        $this->assertTrue($renter->user->fresh()->canRent());
    }

    public function test_admin_dapat_menolak_pendaftar(): void
    {
        $renter = Renter::factory()->pending()->create();

        $this->actingAs($this->admin())
            ->post("/admin/renters/{$renter->id}/reject")
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(RenterStatus::Rejected, $renter->fresh()->status);
        $this->assertFalse($renter->user->fresh()->canRent());
    }

    public function test_pendaftar_yang_sudah_diverifikasi_tidak_bisa_diubah_lagi(): void
    {
        $approved = Renter::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post("/admin/renters/{$approved->id}/reject")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(RenterStatus::Approved, $approved->fresh()->status);
    }

    public function test_pendaftar_yang_tidak_ada_menghasilkan_404(): void
    {
        $this->actingAs($this->admin())->post('/admin/renters/999/approve')->assertNotFound();
    }

    public function test_dashboard_admin_menampilkan_jumlah_pendaftar_menunggu(): void
    {
        Renter::factory()->pending()->count(2)->create();
        Renter::factory()->create();

        $this->actingAs($this->admin())->get('/dashboard')
            ->assertOk()
            ->assertSee('2 menunggu');
    }
}
