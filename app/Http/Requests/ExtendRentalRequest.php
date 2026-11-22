<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi form perpanjangan sewa oleh Admin.
 */
class ExtendRentalRequest extends FormRequest
{
    /** Hanya Admin yang boleh memperpanjang sewa. */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'new_end_date' => ['required', 'date_format:Y-m-d', 'after:today'],
            'payment_proof' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'new_end_date' => 'tanggal selesai baru',
            'payment_proof' => 'bukti pembayaran',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'date_format' => ':attribute harus berformat tahun-bulan-tanggal.',
            'after' => ':attribute harus setelah hari ini.',
            'image' => ':attribute harus berupa gambar.',
            'mimes' => ':attribute harus berformat JPG atau PNG.',
            'payment_proof.max' => 'Ukuran :attribute maksimal 2 MB.',
        ];
    }
}
