<?php

namespace App\Http\Requests;

use App\Enums\GarageType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi filter laporan penyewaan (hanya Admin).
 */
class ReportFilterRequest extends FormRequest
{
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
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'type' => ['nullable', Rule::enum(GarageType::class)],
            'renter' => ['nullable', 'integer', 'exists:renter,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'year' => 'tahun',
            'month' => 'bulan',
            'type' => 'tipe garasi',
            'renter' => 'penyewa',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'integer' => ':attribute tidak valid.',
            'min' => ':attribute tidak valid.',
            'max' => ':attribute tidak valid.',
            'enum' => ':attribute tidak valid.',
            'exists' => ':attribute tidak ditemukan.',
        ];
    }
}
