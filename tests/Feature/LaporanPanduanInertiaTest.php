<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\LagMeasure;
use App\Models\LeadMeasure;
use App\Models\LeadMeasureRealisasi;
use App\Models\User;
use App\Models\Wig;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LaporanPanduanInertiaTest extends TestCase
{
    use RefreshDatabase;

    private Wilayah $wilayah;

    private Cabang $cabang;

    private LeadMeasure $lead;

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

        $wig = Wig::create([
            'kode_wig' => 'WIG-01',
            'nama_wig' => 'WIG Pertama',
            'bidang' => 'JPK',
            'tahun' => $this->tahun,
            'wilayah_id' => $this->wilayah->id,
        ]);

        $lag = LagMeasure::create([
            'kode_lag' => 'LAG-01',
            'wig_id' => $wig->id,
            'cabang_id' => $this->cabang->id,
            'nama_lag' => 'Lag Pertama',
            'tahun' => $this->tahun,
        ]);

        $this->lead = LeadMeasure::create([
            'kode_lead' => 'LEAD-01',
            'lag_measure_id' => $lag->id,
            'wig_id' => $wig->id,
            'cabang_id' => $this->cabang->id,
            'nama_lead' => 'Lead Pertama',
            'is_active' => true,
            'tahun' => $this->tahun,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }

    private function buatRealisasi(int $bulan, int $minggu, int $target, int $realisasi): void
    {
        LeadMeasureRealisasi::create([
            'lead_measure_id' => $this->lead->id,
            'cabang_id' => $this->cabang->id,
            'tahun' => $this->tahun,
            'bulan' => $bulan,
            'minggu_ke' => $minggu,
            'target' => $target,
            'realisasi' => $realisasi,
        ]);
    }

    /* ---------------- Laporan ---------------- */

    public function test_laporan_render_dengan_data(): void
    {
        $this->buatRealisasi(3, 1, 100, 75);

        $this->actingAs($this->admin())
            ->get("/laporan?tahun={$this->tahun}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Laporan/Index')
                ->has('realisasis.data', 1)
                ->where('realisasis.data.0.kode_lead', 'LEAD-01')
                ->where('realisasis.data.0.persentase', 75)
                ->has('namaBulan', 12)
            );
    }

    public function test_filter_bulan_menyaring_hasil(): void
    {
        $this->buatRealisasi(3, 1, 100, 75);
        $this->buatRealisasi(4, 1, 100, 50);

        $this->actingAs($this->admin())
            ->get("/laporan?tahun={$this->tahun}&bulan=4")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('realisasis.data', 1)
                ->where('realisasis.data.0.persentase', 50)
            );
    }

    public function test_kantor_cabang_hanya_melihat_datanya_sendiri(): void
    {
        $lain = Cabang::create([
            'kode' => 'KC-JKT', 'nama' => 'Cabang Jakarta', 'wilayah_id' => $this->wilayah->id,
        ]);

        $this->buatRealisasi(3, 1, 100, 75);

        LeadMeasureRealisasi::create([
            'lead_measure_id' => $this->lead->id,
            'cabang_id' => $lain->id,
            'tahun' => $this->tahun,
            'bulan' => 3,
            'minggu_ke' => 1,
            'target' => 100,
            'realisasi' => 10,
        ]);

        $user = User::factory()->create([
            'wilayah_id' => $this->wilayah->id,
            'cabang_id' => $this->cabang->id,
        ])->assignRole('kantor_cabang');

        $this->actingAs($user)
            ->get("/laporan?tahun={$this->tahun}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('realisasis.data', 1)
                ->where('realisasis.data.0.cabang', 'Cabang Bandung')
            );
    }

    public function test_ekspor_excel_mengunduh_berkas(): void
    {
        $this->buatRealisasi(3, 1, 100, 75);

        $respons = $this->actingAs($this->admin())->get("/laporan/excel?tahun={$this->tahun}");

        $respons->assertOk();
        $this->assertStringContainsString(
            "laporan-realisasi-{$this->tahun}.xlsx",
            $respons->headers->get('content-disposition')
        );
    }

    /* ---------------- Panduan ---------------- */

    public function test_panduan_render(): void
    {
        $this->actingAs($this->admin())
            ->get('/panduan')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Panduan'));
    }

    public function test_panduan_menolak_tamu(): void
    {
        $this->get('/panduan')->assertRedirect('/login');
    }
}
