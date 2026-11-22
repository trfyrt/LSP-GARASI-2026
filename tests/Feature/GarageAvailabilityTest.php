<?php

namespace Tests\Feature;

use App\Enums\GarageStatus;
use App\Enums\GarageType;
use App\Models\Garage;
use App\Models\Invoice;
use App\Models\Rental;
use App\Models\Renter;
use App\Services\GarageAvailability;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ketersediaan garasi dihitung dari tumpang tindih tanggal invoice, bukan dari kolom status.
 */
class GarageAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private GarageAvailability $availability;

    protected function setUp(): void
    {
        parent::setUp();

        $this->availability = new GarageAvailability;
    }

    private function d(string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date);
    }

    private function garage(string $code): Garage
    {
        return Garage::create(['code' => $code, 'type' => GarageType::Car, 'daily_price' => 10000]);
    }

    /** Sewa dengan satu atau beberapa invoice: [[mulai, selesai], ...]. */
    private function rent(Garage $garage, array $periods): Rental
    {
        $renter = Renter::factory()->create();
        $vehicle = $renter->vehicles()->create(['vehicle_type' => 'CAR', 'plate_number' => 'B'.$garage->id, 'brand_model' => 'Avanza']);
        $rental = Rental::create([
            'id_renter' => $renter->id, 'id_garage' => $garage->id, 'id_vehicle' => $vehicle->id, 'rental_type' => 'DAILY',
        ]);
        foreach ($periods as $i => [$start, $end]) {
            Invoice::create([
                'id_rental' => $rental->id, 'type' => $i === 0 ? 'INITIAL' : 'EXTENSION', 'start_date' => $start, 'end_date' => $end,
                'duration_days' => 3, 'rent_fee' => 30000, 'charging_fee' => 0, 'total_fee' => 30000, 'payment_proof_path' => 'x.png',
            ]);
        }

        return $rental;
    }

    public function test_garasi_tanpa_riwayat_selalu_bebas(): void
    {
        $garage = $this->garage('C001');

        $this->assertTrue($this->availability->isFree($garage->id, $this->d('2026-11-21'), $this->d('2026-12-31')));
        $this->assertSame(['C001'], $this->availability->availableGarages($this->d('2026-11-21'), $this->d('2026-11-23'))->pluck('code')->all());
    }

    public function test_tabrakan_periode_dicek_pada_batas_awal_dan_akhir(): void
    {
        $garage = $this->garage('C001');
        $this->rent($garage, [['2026-11-10', '2026-11-15']]);

        // Sepenuhnya sebelum atau sesudah: bebas
        $this->assertTrue($this->availability->isFree($garage->id, $this->d('2026-11-01'), $this->d('2026-11-09')));
        $this->assertTrue($this->availability->isFree($garage->id, $this->d('2026-11-16'), $this->d('2026-11-20')));

        // Menyentuh satu hari di tepi: bertabrakan
        $this->assertFalse($this->availability->isFree($garage->id, $this->d('2026-11-01'), $this->d('2026-11-10')));
        $this->assertFalse($this->availability->isFree($garage->id, $this->d('2026-11-15'), $this->d('2026-11-20')));

        // Di dalam, membungkus, dan menyeberangi: bertabrakan
        $this->assertFalse($this->availability->isFree($garage->id, $this->d('2026-11-11'), $this->d('2026-11-12')));
        $this->assertFalse($this->availability->isFree($garage->id, $this->d('2026-11-01'), $this->d('2026-11-30')));
        $this->assertFalse($this->availability->isFree($garage->id, $this->d('2026-11-13'), $this->d('2026-11-18')));
    }

    public function test_invoice_perpanjangan_ikut_diperhitungkan(): void
    {
        $garage = $this->garage('C001');
        $this->rent($garage, [['2026-11-10', '2026-11-12'], ['2026-11-13', '2026-11-16']]);

        $this->assertFalse($this->availability->isFree($garage->id, $this->d('2026-11-15'), $this->d('2026-11-15')));
        $this->assertTrue($this->availability->isFree($garage->id, $this->d('2026-11-17'), $this->d('2026-11-18')));
    }

    public function test_pemesanan_garasi_lain_tidak_memengaruhi(): void
    {
        $a = $this->garage('C001');
        $b = $this->garage('C002');
        $this->rent($a, [['2026-11-10', '2026-11-15']]);

        $this->assertTrue($this->availability->isFree($b->id, $this->d('2026-11-10'), $this->d('2026-11-15')));
        $this->assertSame(
            ['C002'],
            $this->availability->availableGarages($this->d('2026-11-12'), $this->d('2026-11-13'))->pluck('code')->all(),
        );
    }

    public function test_garasi_dengan_celah_di_antara_dua_pemesanan_dapat_dipakai_pada_celah(): void
    {
        $garage = $this->garage('C001');
        $this->rent($garage, [['2026-11-01', '2026-11-05']]);
        $this->rent($garage, [['2026-11-10', '2026-11-15']]);

        $this->assertTrue($this->availability->isFree($garage->id, $this->d('2026-11-06'), $this->d('2026-11-09')));
        $this->assertFalse($this->availability->isFree($garage->id, $this->d('2026-11-06'), $this->d('2026-11-10')));
    }

    public function test_pemesanan_berikutnya_dicari_setelah_tanggal_tertentu(): void
    {
        $garage = $this->garage('C001');
        $this->rent($garage, [['2026-11-01', '2026-11-05']]);
        $this->rent($garage, [['2026-11-20', '2026-11-22']]);
        $this->rent($garage, [['2026-12-10', '2026-12-12']]);

        $this->assertSame('2026-11-20', $this->availability->nextBookingStart($garage->id, $this->d('2026-11-05'))->toDateString());
        $this->assertSame('2026-12-10', $this->availability->nextBookingStart($garage->id, $this->d('2026-11-22'))->toDateString());
        $this->assertNull($this->availability->nextBookingStart($garage->id, $this->d('2026-12-12')));
        $this->assertNull($this->availability->nextBookingStart($this->garage('C002')->id, $this->d('2026-11-05')));
    }

    public function test_status_used_hanya_untuk_periode_yang_mencakup_hari_ini(): void
    {
        $now = $this->garage('C001');
        $past = $this->garage('C002');
        $future = $this->garage('C003');
        $idle = $this->garage('C004');
        $this->rent($now, [['2026-11-20', '2026-11-25']]);
        $this->rent($past, [['2026-11-01', '2026-11-05']]);
        $this->rent($future, [['2026-12-01', '2026-12-05']]);

        $this->availability->syncStatuses($this->d('2026-11-21'));

        $this->assertSame(GarageStatus::Used, $now->fresh()->status);
        $this->assertSame(GarageStatus::Available, $past->fresh()->status);
        $this->assertSame(GarageStatus::Available, $future->fresh()->status);
        $this->assertSame(GarageStatus::Available, $idle->fresh()->status);
    }

    public function test_status_diperbarui_mengikuti_pergantian_hari(): void
    {
        $garage = $this->garage('C001');
        $this->rent($garage, [['2026-11-22', '2026-11-23']]);

        $this->availability->syncStatuses($this->d('2026-11-21'));
        $this->assertSame(GarageStatus::Available, $garage->fresh()->status); // belum mulai

        $this->availability->syncStatuses($this->d('2026-11-22'));
        $this->assertSame(GarageStatus::Used, $garage->fresh()->status); // hari pertama

        $this->availability->syncStatuses($this->d('2026-11-23'));
        $this->assertSame(GarageStatus::Used, $garage->fresh()->status); // hari terakhir

        $this->availability->syncStatuses($this->d('2026-11-24'));
        $this->assertSame(GarageStatus::Available, $garage->fresh()->status); // sudah lewat
    }
}
