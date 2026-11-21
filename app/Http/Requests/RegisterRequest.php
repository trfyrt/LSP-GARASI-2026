<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi form registrasi penyewa.
 */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'nik' => ['required', 'digits:16', 'unique:renter,nik'],
            'phone_number' => ['required', 'regex:/^(\+62|0)[0-9]{8,14}$/', 'max:20'],
            'email' => ['required', 'email', 'max:255', 'unique:user,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama lengkap',
            'nik' => 'NIK',
            'phone_number' => 'nomor telepon',
            'email' => 'email',
            'password' => 'password',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'max' => ':attribute maksimal :max karakter.',
            'digits' => ':attribute harus terdiri dari :digits digit angka.',
            'email' => 'Format email tidak valid.',
            'unique' => ':attribute sudah terdaftar.',
            'min' => ':attribute minimal :min karakter.',
            'confirmed' => 'Konfirmasi password tidak sama.',
            'phone_number.regex' => 'Nomor telepon harus diawali 0 atau +62 dan hanya berisi angka.',
        ];
    }
}
