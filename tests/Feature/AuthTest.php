<?php

namespace Tests\Feature;

use App\Enums\RenterStatus;
use App\Enums\UserRole;
use App\Models\Renter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Registrasi penyewa, login, logout, dan proteksi halaman.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /** @return array<string, string> */
    private function validRegistration(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Budi Santoso',
            'nik' => '3201010101010001',
            'phone_number' => '081234567890',
            'email' => 'budi@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ], $overrides);
    }

    public function test_halaman_login_dan_registrasi_dapat_dibuka(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    public function test_registrasi_membuat_user_renter_dan_renter_berstatus_pending(): void
    {
        $this->post('/register', $this->validRegistration())
            ->assertRedirect(route('login'));

        $user = User::where('email', 'budi@example.com')->firstOrFail();
        $this->assertSame(UserRole::Renter, $user->role);
        $this->assertNotSame('rahasia123', $user->password);

        $renter = $user->renter;
        $this->assertNotNull($renter);
        $this->assertSame(RenterStatus::Pending, $renter->status);
        $this->assertSame('3201010101010001', $renter->nik);
        $this->assertFalse($user->canRent());
        $this->assertGuest();
    }

    public function test_registrasi_ditolak_jika_data_tidak_valid(): void
    {
        $this->post('/register', $this->validRegistration(['nik' => '12345']))
            ->assertSessionHasErrors('nik');
        $this->post('/register', $this->validRegistration(['phone_number' => 'abc']))
            ->assertSessionHasErrors('phone_number');
        $this->post('/register', $this->validRegistration(['password_confirmation' => 'beda']))
            ->assertSessionHasErrors('password');
        $this->post('/register', $this->validRegistration(['password' => 'pendek', 'password_confirmation' => 'pendek']))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseCount('user', 0);
        $this->assertDatabaseCount('renter', 0);
    }

    public function test_registrasi_ditolak_jika_email_atau_nik_sudah_terdaftar(): void
    {
        $this->post('/register', $this->validRegistration());

        $this->post('/register', $this->validRegistration(['nik' => '3201010101010002']))
            ->assertSessionHasErrors('email');
        $this->post('/register', $this->validRegistration(['email' => 'lain@example.com']))
            ->assertSessionHasErrors('nik');

        $this->assertDatabaseCount('user', 1);
        $this->assertDatabaseCount('renter', 1);
    }

    public function test_penyewa_dapat_login_dan_melihat_status_verifikasi(): void
    {
        $renter = Renter::factory()->pending()->create();

        $this->post('/login', ['email' => $renter->user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($renter->user);
        $this->get('/dashboard')->assertOk()->assertSee('menunggu verifikasi Admin');
    }

    public function test_penyewa_yang_disetujui_melihat_pesan_dapat_menyewa(): void
    {
        $renter = Renter::factory()->create();

        $this->actingAs($renter->user)->get('/dashboard')
            ->assertOk()
            ->assertSee('dapat menyewa garasi');
    }

    public function test_admin_dapat_login_tanpa_profil_penyewa(): void
    {
        $admin = User::factory()->admin()->create();

        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->get('/dashboard')->assertOk()->assertSee('Panel Admin');
    }

    public function test_login_gagal_dengan_password_salah(): void
    {
        $user = User::factory()->create();

        $this->from('/login')
            ->post('/login', ['email' => $user->email, 'password' => 'salah'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_logout_mengakhiri_sesi(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_tamu_diarahkan_ke_login_dan_pengguna_login_tidak_melihat_form_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get('/')->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())->get('/login')->assertRedirect();
    }
}
