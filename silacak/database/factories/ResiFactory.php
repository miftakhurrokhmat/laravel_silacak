<?php

namespace Database\Factories;

use App\Models\Pelanggan;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResiFactory extends Factory
{
    /**
     * Counter statis untuk nomor urut resi.
     * Menggunakan static agar tidak duplikat dalam 1x generate.
     */
    protected static int $sequence = 0;

    public function definition(): array
    {
        // Berat dibuat sekali agar berat_aktual & berat_tagih konsisten
        $berat = $this->faker->randomFloat(2, 1, 10);

        // Nomor urut resi (4 digit, contoh: 0001, 0002, ...)
        static::$sequence++;
        $seq = str_pad((string) static::$sequence, 4, '0', STR_PAD_LEFT);

        return [
            // === Identitas Resi ===
            'nomor_resi'       => 'SLC-' . now()->format('Ymd') . '-' . $seq,
            'pelanggan_id'     => Pelanggan::factory(), // otomatis buat pelanggan baru
            'cabang_asal_id'   => 1,
            'cabang_tujuan_id' => 1,
            'layanan_id'       => 1,
            'user_id'          => 1,

            // === Data Penerima ===
            'nama_penerima'    => $this->faker->name(),
            'telepon_penerima' => $this->faker->phoneNumber(),
            'alamat_penerima'  => $this->faker->address(),

            // === Dimensi & Berat (WAJIB) ===
            'berat_aktual'     => $berat,
            'berat_tagih'      => $berat, // <-- INI YANG TADI ERROR
            'panjang'          => $this->faker->numberBetween(10, 100),
            'lebar'            => $this->faker->numberBetween(10, 100),
            'tinggi'           => $this->faker->numberBetween(10, 100),

            // === Biaya ===
            'nilai_barang'     => $this->faker->numberBetween(50000, 1000000),
            'biaya_dasar'      => $this->faker->numberBetween(10000, 50000),
            'diskon'           => 0,
            'asuransi'         => 0,
            'total_biaya'      => $this->faker->numberBetween(10000, 100000),

            // === Status (sesuai ENUM di DB) ===
            'status'           => $this->faker->randomElement([
                'pending', 'pickup', 'transit', 'delivery', 'terkirim'
            ]),

            'catatan'          => null,
        ];
    }
}