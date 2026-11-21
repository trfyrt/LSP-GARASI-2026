<?php

namespace Tests\Unit;

use App\Enums\GarageType;
use App\Services\GarageIdGenerator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit test logika pembuatan kode garasi (tanpa database).
 */
class GarageIdGeneratorTest extends TestCase
{
    private GarageIdGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new GarageIdGenerator;
    }

    public function test_kode_pertama_dimulai_dari_001(): void
    {
        $this->assertSame(['S001', 'S002', 'S003'], $this->generator->generate(GarageType::Bike, 0, 3));
    }

    public function test_kode_melanjutkan_dari_nomor_terakhir(): void
    {
        $this->assertSame(['C006', 'C007'], $this->generator->generate(GarageType::Car, 5, 2));
    }

    public function test_nomor_diberi_padding_tiga_digit(): void
    {
        $this->assertSame(['E009', 'E010', 'E011'], $this->generator->generate(GarageType::ElectricCar, 8, 3));
        $this->assertSame(['C099', 'C100'], $this->generator->generate(GarageType::Car, 98, 2));
    }

    #[DataProvider('awalanPerTipe')]
    public function test_awalan_sesuai_tipe(GarageType $type, string $expected): void
    {
        $this->assertSame([$expected], $this->generator->generate($type, 0, 1));
    }

    /** @return array<string, array{GarageType, string}> */
    public static function awalanPerTipe(): array
    {
        return [
            'sepeda' => [GarageType::Bike, 'S001'],
            'mobil' => [GarageType::Car, 'C001'],
            'mobil listrik' => [GarageType::ElectricCar, 'E001'],
        ];
    }

    public function test_batas_999_per_tipe_masih_diperbolehkan(): void
    {
        $this->assertSame(['E999'], $this->generator->generate(GarageType::ElectricCar, 998, 1));

        $codes = $this->generator->generate(GarageType::Bike, 0, 999);
        $this->assertCount(999, $codes);
        $this->assertSame('S001', $codes[0]);
        $this->assertSame('S999', $codes[998]);
    }

    public function test_melewati_batas_999_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Sisa slot: 1');

        $this->generator->generate(GarageType::Car, 998, 2);
    }

    public function test_batch_lebih_dari_999_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->generator->generate(GarageType::Bike, 0, 1000);
    }

    public function test_jumlah_nol_atau_negatif_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->generator->generate(GarageType::Bike, 0, 0);
    }

    public function test_nomor_diambil_dari_kode(): void
    {
        $this->assertSame(0, $this->generator->numberFromCode(null));
        $this->assertSame(1, $this->generator->numberFromCode('S001'));
        $this->assertSame(42, $this->generator->numberFromCode('C042'));
        $this->assertSame(999, $this->generator->numberFromCode('E999'));
    }
}
