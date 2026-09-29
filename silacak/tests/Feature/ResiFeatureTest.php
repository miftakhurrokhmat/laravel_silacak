<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Cabang;
use App\Models\Pelanggan;
use App\Models\Layanan;
use App\Models\Resi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResiFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function seedBasicData(): array
    {
        $user = User::factory()->create();
        $cabangAsal = Cabang::create([
            'kode' => 'JKT',
            'nama' => 'Jakarta',
            'kota' => 'Jakarta',
        ]);
        $cabangTujuan = Cabang::create([
            'kode' => 'BDG',
            'nama' => 'Bandung',
            'kota' => 'Bandung',
        ]);
        $pelanggan = Pelanggan::create([
            'nama' => 'PT Maju Jaya',
            'email' => 'maju@test.com',
            'telepon' => '081234567890',
            'is_member' => false,
        ]);
        $layanan = Layanan::create([
            'kode' => 'REG',
            'nama' => 'Reguler',
            'tarif_per_kg' => 9000,
            'min_kg' => 1,
            'asuransi_persen' => 0.2,
            'asuransi_min_nilai' => 1000000,
            'aktif' => true,
        ]);

        return compact('user', 'cabangAsal', 'cabangTujuan', 'pelanggan', 'layanan');
    }

    public function test_user_bisa_membuat_resi_baru(): void
    {
        $data = $this->seedBasicData();

        $response = $this->actingAs($data['user'])->post('/resi', [
            'pelanggan_id' => $data['pelanggan']->id,
            'cabang_asal_id' => $data['cabangAsal']->id,
            'cabang_tujuan_id' => $data['cabangTujuan']->id,
            'layanan_id' => $data['layanan']->id,
            'nama_penerima' => 'Budi Santoso',
            'telepon_penerima' => '089876543210',
            'alamat_penerima' => 'Jl. Merdeka No. 10, Bandung',
            'berat_aktual' => 1.3,
            'nilai_barang' => 0,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('resi', [
            'nama_penerima' => 'Budi Santoso',
            'berat_tagih' => 2,
        ]);

        $resi = Resi::first();
        $this->assertStringStartsWith('SLC-', $resi->nomor_resi);
    }

    public function test_resi_menolak_cabang_asal_dan_tujuan_yang_sama(): void
    {
        $data = $this->seedBasicData();

        $response = $this->actingAs($data['user'])->post('/resi', [
            'pelanggan_id' => $data['pelanggan']->id,
            'cabang_asal_id' => $data['cabangAsal']->id,
            'cabang_tujuan_id' => $data['cabangAsal']->id,
            'layanan_id' => $data['layanan']->id,
            'nama_penerima' => 'Test',
            'telepon_penerima' => '08123',
            'alamat_penerima' => 'Test',
            'berat_aktual' => 2,
            'nilai_barang' => 0,
        ]);

        $response->assertSessionHasErrors('cabang_tujuan_id');
        $this->assertDatabaseCount('resi', 0);
    }

    public function test_asuransi_dihitung_untuk_nilai_barang_diatas_1_juta(): void
    {
        $data = $this->seedBasicData();

        $this->actingAs($data['user'])->post('/resi', [
            'pelanggan_id' => $data['pelanggan']->id,
            'cabang_asal_id' => $data['cabangAsal']->id,
            'cabang_tujuan_id' => $data['cabangTujuan']->id,
            'layanan_id' => $data['layanan']->id,
            'nama_penerima' => 'Test',
            'telepon_penerima' => '08123',
            'alamat_penerima' => 'Test',
            'berat_aktual' => 2,
            'nilai_barang' => 2000000,
        ]);

        $resi = Resi::first();
        $this->assertEquals(4000, $resi->asuransi);
        $this->assertEquals(22000, $resi->total_biaya);
    }

    public function test_tracking_log_dibuat_otomatis_saat_resi_baru(): void
    {
        $data = $this->seedBasicData();

        $this->actingAs($data['user'])->post('/resi', [
            'pelanggan_id' => $data['pelanggan']->id,
            'cabang_asal_id' => $data['cabangAsal']->id,
            'cabang_tujuan_id' => $data['cabangTujuan']->id,
            'layanan_id' => $data['layanan']->id,
            'nama_penerima' => 'Test',
            'telepon_penerima' => '08123',
            'alamat_penerima' => 'Test',
            'berat_aktual' => 2,
            'nilai_barang' => 0,
        ]);

        $this->assertDatabaseHas('tracking_log', [
            'status' => 'pending',
            'lokasi' => 'Jakarta',
        ]);
    }
}