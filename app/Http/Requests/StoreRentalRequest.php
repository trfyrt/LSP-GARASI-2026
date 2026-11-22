<?php

namespace App\Http\Requests;

use App\Enums\RentalType;
use App\Enums\VehicleType;
use App\Services\RentalValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi format form sewa baru. Aturan bisnis (plat, kompatibilitas, batas hari)
 * dijaga oleh {@see RentalValidator}.
 */
class StoreRentalRequest extends FormRequest
{
    /** Hanya penyewa yang sudah diverifikasi (Approved) yang boleh menyewa. */
    public function authorize(): bool
    {
        return $this->user()?->renter?->canRent() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'vehicle_type' => ['required', Rule::enum(VehicleType::class)],
            'plate_number' => ['nullable', 'string', 'max:12', 'regex:/^[A-Za-z0-9 ]+$/'],
            'brand_model' => ['required', 'string', 'max:255'],
            'battery_kwh' => ['nullable', 'numeric', 'min:0.1', 'max:999.99'],
            'rental_type' => ['required', Rule::enum(RentalType::class)],
            'days' => ['nullable', 'integer', 'min:1', 'max:30'],
            'id_garage' => ['required', 'integer', 'exists:garage,id'],
            'payment_proof' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'vehicle_type' => 'tipe kendaraan',
            'plate_number' => 'nomor plat',
            'brand_model' => 'merek & tipe',
            'battery_kwh' => 'kapasitas listrik',
            'rental_type' => 'tipe sewa',
            'days' => 'jumlah hari',
            'id_garage' => 'garasi',
            'payment_proof' => 'bukti pelunasan',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'enum' => ':attribute tidak valid.',
            'integer' => ':attribute harus berupa angka bulat.',
            'numeric' => ':attribute harus berupa angka.',
            'min' => ':attribute minimal :min.',
            'max' => ':attribute maksimal :max.',
            'exists' => ':attribute tidak ditemukan.',
            'regex' => ':attribute hanya boleh huruf, angka, dan spasi.',
            'image' => ':attribute harus berupa gambar.',
            'mimes' => ':attribute harus berformat JPG atau PNG.',
            'payment_proof.max' => 'Ukuran :attribute maksimal 2 MB.',
        ];
    }
}
