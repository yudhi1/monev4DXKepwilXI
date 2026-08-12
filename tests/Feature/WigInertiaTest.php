<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\LagMeasure;
use App\Models\User;
use App\Models\Wig;
use App\Models\WigRealisasi;
use App\Models\WigTarget;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WigInertiaTest extends TestCase
{
    use RefreshDatabase;

    private Wilayah $wilayah;

    private Cabang $cabang;

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
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }

    private function buatWig(array $ubah = []): Wig
    {
        return Wig::create(array_merge([
            'kode_wig' => 'WIG-01',
            'nama_wig' => 'WIG Pertama',
            'bidang' => 'JPK',
            'tahun' => $this->tahun,
            'wilayah_id' => $this->wilayah->id,
        ], $ubah));
    }

    /* ---------------- Data WIG ---------------- */

    public function test_daftar_wig_tampil(): void
    {
        $this->buatWig();

        $this->actingAs($this->admin())
            ->get('/wigs')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Wig/Index')
                ->has('wigs.data', 1)
                ->where('wigs.data.0.kode_wig', 'WIG-01')
            );
    }

    public function test_menyimpan_wig_mencatat_pembuatnya(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/wigs', [
                'kode_wig' => 'WIG-09',
                'nama_wig' => 'WIG Baru',
                'bidang' => 'KML',
                'tahun' => $this->tahun,
                'wilayah_id' => $this->wilayah->id,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('wigs', ['kode_wig' => 'WIG-09', 'created_by' => $admin->id]);
    }

    public function test_bidang_di_luar_daftar_ditolak(): void
    {
        $this->actingAs($this->admin())
            ->post('/wigs', [
                'kode_wig' => 'WIG-09',
                'nama_wig' => 'WIG Baru',
                'bidang' => 'TIDAK-ADA',
                'tahun' => $this->tahun,
            ])
            ->assertSessionHasErrors('bidang');
    }

    public function test_wig_dengan_lag_tidak_bisa_dihapus(): void
    {
        $wig = $this->buatWig();
        LagMeasure::create([
            'kode_lag' => 'BDG-01-'.$this->tahun,
            'wig_id' => $wig->id,
            'cabang_id' => $this->cabang->id,
            'nama_lag' => 'Lag Pertama',
            'tahun' => $this->tahun,
        ]);

        $this->actingAs($this->admin())
            ->delete("/wigs/{$wig->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('wigs', ['id' => $wig->id]);
    }

    public function test_kedeputian_wilayah_hanya_melihat_wig_wilayahnya(): void
    {
        $lain = Wilayah::create(['kode' => 'W02', 'nama' => 'Wilayah Dua']);
        $this->buatWig();
        $this->buatWig(['kode_wig' => 'WIG-02', 'wilayah_id' => $lain->id]);

        $user = User::factory()->create(['wilayah_id' => $this->wilayah->id])->assignRole('kedeputian_wilayah');

        $this->actingAs($user)
            ->get('/wigs')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('wigs.data', 1)
                ->where('wigs.data.0.kode_wig', 'WIG-01')
            );
    }

    /* ---------------- Target WIG ---------------- */

    public function test_halaman_target_menyiapkan_baris_untuk_tiap_cabang(): void
    {
        $wig = $this->buatWig();
        Cabang::create(['kode' => 'KC-JKT', 'nama' => 'Cabang Jakarta', 'wilayah_id' => $this->wilayah->id]);

        $this->actingAs($this->admin())
            ->get("/wig-targets?wig_id={$wig->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Wig/Target')
                ->has('baris', 2)
                ->where('baris.0.satuan', 'Rp')
            );
    }

    public function test_menyimpan_target_per_cabang(): void
    {
        $wig = $this->buatWig();

        $this->actingAs($this->admin())
            ->post('/wig-targets', [
                'wig_id' => $wig->id,
                'baris' => [[
                    'cabang_id' => $this->cabang->id,
                    'nilai_awal' => 100,
                    'nilai_target' => 500,
                    'satuan' => 'orang',
                    'tanggal_target' => '2026-12-31',
                ]],
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('wig_targets', [
            'wig_id' => $wig->id,
            'cabang_id' => $this->cabang->id,
            'nilai_target' => 500,
            'satuan' => 'orang',
        ]);
    }

    public function test_target_cabang_di_luar_wilayah_diabaikan(): void
    {
        $wig = $this->buatWig();
        $lainWilayah = Wilayah::create(['kode' => 'W02', 'nama' => 'Wilayah Dua']);
        $cabangLain = Cabang::create([
            'kode' => 'KC-SBY', 'nama' => 'Cabang Surabaya', 'wilayah_id' => $lainWilayah->id,
        ]);

        $user = User::factory()->create(['wilayah_id' => $this->wilayah->id])->assignRole('kedeputian_wilayah');

        $this->actingAs($user)
            ->post('/wig-targets', [
                'wig_id' => $wig->id,
                'baris' => [[
                    'cabang_id' => $cabangLain->id,
                    'nilai_awal' => 0,
                    'nilai_target' => 999,
                    'satuan' => 'Rp',
                    'tanggal_target' => null,
                ]],
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('wig_targets', ['cabang_id' => $cabangLain->id]);
    }

    /* ---------------- Realisasi WIG ---------------- */

    public function test_halaman_realisasi_selalu_menyiapkan_dua_belas_baris(): void
    {
        $wig = $this->buatWig();

        $this->actingAs($this->admin())
            ->get("/wig-realisasi?wig_id={$wig->id}&cabang_id={$this->cabang->id}&tahun={$this->tahun}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Wig/Realisasi')
                ->has('baris', 12)
                ->where('baris.0.bulan', 1)
                ->where('baris.11.bulan', 12)
            );
    }

    public function test_menyimpan_realisasi_dua_belas_bulan(): void
    {
        $wig = $this->buatWig();

        $baris = [];

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $baris[] = ['bulan' => $bulan, 'nilai' => $bulan * 10, 'catatan' => null];
        }

        $this->actingAs($this->admin())
            ->post('/wig-realisasi', [
                'wig_id' => $wig->id,
                'cabang_id' => $this->cabang->id,
                'tahun' => $this->tahun,
                'baris' => $baris,
            ])
            ->assertSessionHas('success');

        $this->assertSame(12, WigRealisasi::where('wig_id', $wig->id)->count());
        $this->assertDatabaseHas('wig_realisasis', ['wig_id' => $wig->id, 'bulan' => 12, 'nilai' => 120]);
    }

    public function test_progres_dihitung_terhadap_rentang_awal_ke_target(): void
    {
        $wig = $this->buatWig();

        WigTarget::create([
            'wig_id' => $wig->id,
            'cabang_id' => $this->cabang->id,
            'nilai_awal' => 100,
            'nilai_target' => 600,
            'satuan' => 'orang',
        ]);

        WigRealisasi::create([
            'wig_id' => $wig->id,
            'cabang_id' => $this->cabang->id,
            'tahun' => $this->tahun,
            'bulan' => 1,
            'nilai' => 250,
        ]);

        // Rentang 500, realisasi 250 → 50%
        $this->actingAs($this->admin())
            ->get("/wig-realisasi?wig_id={$wig->id}&cabang_id={$this->cabang->id}&tahun={$this->tahun}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('ringkasan.total_realisasi', 250)
                ->where('ringkasan.progres', 50)
                ->where('ringkasan.nilai_sekarang', 350)
            );
    }

    public function test_kantor_cabang_tidak_bisa_menyimpan_untuk_cabang_lain(): void
    {
        $wig = $this->buatWig();
        $lain = Cabang::create([
            'kode' => 'KC-JKT', 'nama' => 'Cabang Jakarta', 'wilayah_id' => $this->wilayah->id,
        ]);

        $user = User::factory()->create([
            'wilayah_id' => $this->wilayah->id,
            'cabang_id' => $this->cabang->id,
        ])->assignRole('kantor_cabang');

        $baris = [];

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $baris[] = ['bulan' => $bulan, 'nilai' => 10, 'catatan' => null];
        }

        $this->actingAs($user)
            ->post('/wig-realisasi', [
                'wig_id' => $wig->id,
                'cabang_id' => $lain->id,
                'tahun' => $this->tahun,
                'baris' => $baris,
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('wig_realisasis', ['cabang_id' => $lain->id]);
    }
}
