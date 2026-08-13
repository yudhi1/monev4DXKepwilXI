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

    public function test_filter_bidang_menyaring_hasil(): void
    {
        $this->buatWig(['kode_wig' => 'WIG-JPK', 'bidang' => 'JPK']);
        $this->buatWig(['kode_wig' => 'WIG-KML', 'bidang' => 'KML']);

        $this->actingAs($this->admin())
            ->get('/wigs?bidang=KML')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('wigs.data', 1)
                ->where('wigs.data.0.kode_wig', 'WIG-KML')
                ->where('filter.bidang', 'KML')
            );
    }

    public function test_bidang_tidak_dikenal_diabaikan(): void
    {
        $this->buatWig();

        $this->actingAs($this->admin())
            ->get('/wigs?bidang=TIDAK-ADA')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('wigs.data', 1)
                ->where('filter.bidang', null)
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

    /* ---------------- Target & Realisasi WIG ---------------- */

    public function test_halaman_capaian_menyiapkan_baris_per_unit_kerja(): void
    {
        $wig = $this->buatWig();
        Cabang::create(['kode' => 'KC-JKT', 'nama' => 'Cabang Jakarta', 'wilayah_id' => $this->wilayah->id]);

        $this->actingAs($this->admin())
            ->get("/wig-capaian?wig_id={$wig->id}&tahun={$this->tahun}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Wig/Capaian')
                ->has('baris', 2)
                ->has('baris.0.bulan', 12)
                ->where('baris.0.satuan', 'Rp')
                ->where('bisaUbahTarget', true)
            );
    }

    public function test_menyimpan_target_tahunan_dan_bulanan_sekaligus(): void
    {
        $wig = $this->buatWig();

        $this->actingAs($this->admin())
            ->post('/wig-capaian', [
                'wig_id' => $wig->id,
                'tahun' => $this->tahun,
                'baris' => [[
                    'cabang_id' => $this->cabang->id,
                    'nilai_target' => 1200,
                    'satuan' => 'orang',
                    'tanggal_target' => '2026-12-31',
                    'bulan' => $this->duaBelasBulan(100, 90),
                ]],
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('wig_targets', [
            'wig_id' => $wig->id,
            'cabang_id' => $this->cabang->id,
            'nilai_target' => 1200,
            'satuan' => 'orang',
        ]);

        $this->assertSame(12, WigRealisasi::where('wig_id', $wig->id)->count());
        $this->assertDatabaseHas('wig_realisasis', [
            'wig_id' => $wig->id,
            'bulan' => 1,
            'target' => 100,
            'nilai' => 90,
        ]);
    }

    public function test_kantor_cabang_tidak_dapat_mengubah_target(): void
    {
        $wig = $this->buatWig();

        WigTarget::create([
            'wig_id' => $wig->id,
            'cabang_id' => $this->cabang->id,
            'nilai_awal' => 0,
            'nilai_target' => 500,
            'satuan' => 'Rp',
        ]);

        $user = User::factory()->create([
            'wilayah_id' => $this->wilayah->id,
            'cabang_id' => $this->cabang->id,
        ])->assignRole('kantor_cabang');

        $this->actingAs($user)
            ->post('/wig-capaian', [
                'wig_id' => $wig->id,
                'tahun' => $this->tahun,
                'baris' => [[
                    'cabang_id' => $this->cabang->id,
                    'nilai_target' => 999999,
                    'satuan' => 'orang',
                    'tanggal_target' => null,
                    'bulan' => $this->duaBelasBulan(100, 90),
                ]],
            ])
            ->assertSessionHas('success');

        // Target tahunan maupun target bulanan tetap seperti semula.
        $this->assertDatabaseHas('wig_targets', ['wig_id' => $wig->id, 'nilai_target' => 500, 'satuan' => 'Rp']);
        $this->assertDatabaseHas('wig_realisasis', ['wig_id' => $wig->id, 'bulan' => 1, 'target' => 0, 'nilai' => 90]);
    }

    public function test_unit_kerja_di_luar_wilayah_diabaikan(): void
    {
        $wig = $this->buatWig();
        $wilayahLain = Wilayah::create(['kode' => 'W02', 'nama' => 'Wilayah Dua']);
        $cabangLuar = Cabang::create([
            'kode' => 'KC-SBY', 'nama' => 'Cabang Surabaya', 'wilayah_id' => $wilayahLain->id,
        ]);

        $user = User::factory()->create(['wilayah_id' => $this->wilayah->id])->assignRole('kedeputian_wilayah');

        $this->actingAs($user)
            ->post('/wig-capaian', [
                'wig_id' => $wig->id,
                'tahun' => $this->tahun,
                'baris' => [[
                    'cabang_id' => $cabangLuar->id,
                    'nilai_target' => 777,
                    'satuan' => 'Rp',
                    'tanggal_target' => null,
                    'bulan' => $this->duaBelasBulan(10, 10),
                ]],
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('wig_targets', ['cabang_id' => $cabangLuar->id]);
        $this->assertDatabaseMissing('wig_realisasis', ['cabang_id' => $cabangLuar->id]);
    }

    public function test_kantor_cabang_hanya_melihat_barisnya_sendiri(): void
    {
        $wig = $this->buatWig();
        Cabang::create(['kode' => 'KC-JKT', 'nama' => 'Cabang Jakarta', 'wilayah_id' => $this->wilayah->id]);

        $user = User::factory()->create([
            'wilayah_id' => $this->wilayah->id,
            'cabang_id' => $this->cabang->id,
        ])->assignRole('kantor_cabang');

        $this->actingAs($user)
            ->get("/wig-capaian?wig_id={$wig->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('baris', 1)
                ->where('baris.0.cabang_id', $this->cabang->id)
                ->where('bisaUbahTarget', false)
            );
    }

    /** Isian dua belas bulan dengan target dan realisasi yang sama tiap bulan. */
    private function duaBelasBulan(float $target, float $realisasi): array
    {
        $bulan = [];

        for ($b = 1; $b <= 12; $b++) {
            $bulan[] = ['bulan' => $b, 'target' => $target, 'realisasi' => $realisasi];
        }

        return $bulan;
    }
}
