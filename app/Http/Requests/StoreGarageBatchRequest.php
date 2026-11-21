<?php

namespace App\Http\Requests;

use App\Enums\GarageType;
use App\Services\GarageIdGenerator;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi form pembuatan garasi batch oleh Admin.
 */
class StoreGarageBatchRequest extends FormRequest
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
            'type' => ['required', Rule::enum(GarageType::class)],
            'count' => ['required', 'integer', 'min:1', 'max:'.GarageIdGenerator::MAX_PER_TYPE],
            'daily_price' => ['required', 'integer', 'min:1', 'max:100000000'],
            'charging_price' => [
                Rule::requiredIf(fn () => $this->input('type') === GarageType::ElectricCar->value),
                'nullable', 'integer', 'min:1', 'max:1000000',
            ],
        ];
    }

    /**
     * Pastikan jumlah tidak melewati sisa slot kode (maksimal 999 per tipe).
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $type = GarageType::from($this->input('type'));
            $remaining = app(GarageIdGenerator::class)->remaining($type);

            if ((int) $this->input('count') > $remaining) {
                $validator->errors()->add(
                    'count',
                    "Kode {$type->label()} maksimal ".GarageIdGenerator::MAX_PER_TYPE.". Sisa slot: {$remaining}."
                );
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type' => 'tipe garasi',
            'count' => 'jumlah garasi',
            'daily_price' => 'harga sewa harian',
            'charging_price' => 'harga isi listrik per kWh',
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
            'min' => ':attribute minimal :min.',
            'max' => ':attribute maksimal :max.',
        ];
    }
}
