<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\MonevIuranRealisasi;
use App\Models\MonevSegmen;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MonevIuranInertiaTest extends TestCase
{
    use RefreshDatabase;

    private Wilayah $wilayah;

    private Cabang $cabang;

    private MonevSegmen $segmen;

    private int $tahun;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'kedeputian_wilayah', 'kantor_cabang'] as $role) {
            Role::findOrCreate($role);
        }

        $this->tahun = (int) date('Y');
        $this->wilayah = Wilayah::create(['kode' => 'W01', 'nama' => 'Wilayah Satu']);
        $this->cabang = Cabang::create([
            'kode' => 'KC-BDG', 'nama' => 'Cabang Bandung', 'wilayah_id' => $this->wilayah->id,
        ]);
        $this->segmen = MonevSegmen::create(['nama' => 'PBPU', 'urutan' => 1, 'is_active' => true]);
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }

    private function isiBaris(array $ubah = []): array
    {
        return array_merge([
            'cabang_id' => $this->cabang->id,
            'segmen_id' => $this->segmen->id,
            'tahun' => $this->tahun,
            'bulan' => 3,
            'realisasi_sd_bulan_lalu' => 1000,
            'mg1' => 10,
            'mg2' => 20,
            'mg3' => 30,
            'mg4' => 40,
            'keterangan' => null,
        ], $ubah);
    }

    /* ---------------- Master Segmen ---------------- */

    public function test_daftar_segmen_tampil(): void
    {
        $this->actingAs($this->admin())
            ->get('/monev-iuran/segmen')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('MonevIuran/Segmen')
                ->has('segmens', 1)
                ->where('urutanBerikutnya', 2)
            );
    }

    public function test_nama_segmen_wajib_unik(): void
    {
        $this->actingAs($this->admin())
            ->post('/monev-iuran/segmen', ['nama' => 'PBPU', 'urutan' => 2, 'is_active' => true])
            ->assertSessionHasErrors('nama');
    }

    public function test_segmen_dengan_realisasi_tidak_bisa_dihapus(): void
    {
        MonevIuranRealisasi::create($this->isiBaris());

        $this->actingAs($this->admin())
            ->delete("/monev-iuran/segmen/{$this->segmen->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('monev_segmens', ['id' => $this->segmen->id]);
    }

    /* ---------------- Input realisasi ---------------- */

    public function test_baris_kosong_sebelum_cabang_dipilih(): void
    {
        $this->actingAs($this->admin())
            ->get('/monev-iuran/input')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('MonevIuran/Input')
                ->has('baris', 0)
                ->where('konsolidasi', false)
            );
    }

    public function test_segmen_nonaktif_tidak_muncul(): void
    {
        $this->segmen->update(['is_active' => false]);

        $this->actingAs($this->admin())
            ->get("/monev-iuran/input?pilihan={$this->cabang->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page->has('baris', 0));
    }

    public function test_menyimpan_baris_segmen(): void
    {
        $this->actingAs($this->admin())
            ->post('/monev-iuran/input', $this->isiBaris())
            ->assertSessionHas('success');

        $this->assertDatabaseHas('monev_iuran_realisasis', [
            'cabang_id' => $this->cabang->id,
            'segmen_id' => $this->segmen->id,
            'mg4' => 40,
        ]);
    }

    public function test_nilai_negatif_ditolak(): void
    {
        $this->actingAs($this->admin())
            ->post('/monev-iuran/input', $this->isiBaris(['mg1' => -5]))
            ->assertSessionHasErrors('mg1');
    }

    public function test_konsolidasi_menjumlahkan_semua_cabang(): void
    {
        $lain = Cabang::create([
            'kode' => 'KC-JKT', 'nama' => 'Cabang Jakarta', 'wilayah_id' => $this->wilayah->id,
        ]);

        MonevIuranRealisasi::create($this->isiBaris());
        MonevIuranRealisasi::create($this->isiBaris(['cabang_id' => $lain->id, 'mg1' => 5]));

        $this->actingAs($this->admin())
            ->get("/monev-iuran/input?pilihan=konsolidasi&tahun={$this->tahun}&bulan=3")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('konsolidasi', true)
                ->where('baris.0.mg1', 15)
                ->where('baris.0.terkunci', true)
            );
    }

    /* ---------------- Kunci periode ---------------- */

    public function test_mengunci_periode_menandai_final(): void
    {
        MonevIuranRealisasi::create($this->isiBaris());

        $this->actingAs($this->admin())
            ->post('/monev-iuran/kunci', [
                'cabang_id' => $this->cabang->id,
                'tahun' => $this->tahun,
                'bulan' => 3,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('monev_iuran_realisasis', [
            'cabang_id' => $this->cabang->id,
            'status_periode' => 'final',
        ]);
    }

    public function test_periode_tanpa_data_tidak_bisa_dikunci(): void
    {
        $this->actingAs($this->admin())
            ->post('/monev-iuran/kunci', [
                'cabang_id' => $this->cabang->id,
                'tahun' => $this->tahun,
                'bulan' => 3,
            ])
            ->assertSessionHas('error');
    }

    public function test_periode_final_menolak_penyimpanan(): void
    {
        MonevIuranRealisasi::create($this->isiBaris(['status_periode' => 'final']));

        $this->actingAs($this->admin())
            ->post('/monev-iuran/input', $this->isiBaris(['mg1' => 999]))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('monev_iuran_realisasis', ['mg1' => 999]);
    }

    public function test_hanya_admin_yang_bisa_membuka_kunci(): void
    {
        MonevIuranRealisasi::create($this->isiBaris(['status_periode' => 'final']));

        $user = User::factory()->create([
            'wilayah_id' => $this->wilayah->id,
            'cabang_id' => $this->cabang->id,
        ])->assignRole('kantor_cabang');

        $this->actingAs($user)
            ->post('/monev-iuran/buka-kunci', [
                'cabang_id' => $this->cabang->id,
                'tahun' => $this->tahun,
                'bulan' => 3,
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseHas('monev_iuran_realisasis', ['status_periode' => 'final']);
    }

    public function test_admin_bisa_membuka_kunci(): void
    {
        MonevIuranRealisasi::create($this->isiBaris(['status_periode' => 'final']));

        $this->actingAs($this->admin())
            ->post('/monev-iuran/buka-kunci', [
                'cabang_id' => $this->cabang->id,
                'tahun' => $this->tahun,
                'bulan' => 3,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('monev_iuran_realisasis', ['status_periode' => 'draft']);
    }

    public function test_kantor_cabang_tidak_bisa_menyimpan_untuk_cabang_lain(): void
    {
        $lain = Cabang::create([
            'kode' => 'KC-JKT', 'nama' => 'Cabang Jakarta', 'wilayah_id' => $this->wilayah->id,
        ]);

        $user = User::factory()->create([
            'wilayah_id' => $this->wilayah->id,
            'cabang_id' => $this->cabang->id,
        ])->assignRole('kantor_cabang');

        $this->actingAs($user)
            ->post('/monev-iuran/input', $this->isiBaris(['cabang_id' => $lain->id]))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('monev_iuran_realisasis', ['cabang_id' => $lain->id]);
    }

    public function test_kantor_cabang_terkunci_pada_cabangnya_sendiri(): void
    {
        $user = User::factory()->create([
            'wilayah_id' => $this->wilayah->id,
            'cabang_id' => $this->cabang->id,
        ])->assignRole('kantor_cabang');

        $this->actingAs($user)
            ->get('/monev-iuran/input?pilihan=konsolidasi')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filter.cabang_id', $this->cabang->id)
                ->where('konsolidasi', false)
                ->where('terkunciCabang', true)
            );
    }
}
