<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\LagMeasure;
use App\Models\LeadMeasure;
use App\Models\LeadMeasureRealisasi;
use App\Models\User;
use App\Models\Wig;
use App\Models\WigRealisasi;
use App\Models\WigTarget;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardCabangInertiaTest extends TestCase
{
    use RefreshDatabase;

    private Wilayah $wilayah;

    private Cabang $cabang;

    private Wig $wig;

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
        $this->wig = Wig::create([
            'kode_wig' => 'WIG-01',
            'nama_wig' => 'WIG Pertama',
            'bidang' => 'Kepesertaan',
            'tahun' => $this->tahun,
            'wilayah_id' => $this->wilayah->id,
        ]);

        $lag = LagMeasure::create([
            'kode_lag' => 'BDG-01-'.$this->tahun,
            'wig_id' => $this->wig->id,
            'cabang_id' => $this->cabang->id,
            'nama_lag' => 'Lag Pertama',
            'tahun' => $this->tahun,
        ]);

        $this->lead = LeadMeasure::create([
            'kode_lead' => 'LEAD-KEPESERTAAN-BDG-01-'.$this->tahun,
            'lag_measure_id' => $lag->id,
            'wig_id' => $this->wig->id,
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

    public function test_dashboard_render_dengan_ringkasan(): void
    {
        $this->buatRealisasi(3, 1, 100, 120);

        $this->actingAs($this->admin())
            ->get("/dashboard-cabang?tahun={$this->tahun}&bulan=3&minggu=1&cabang_id={$this->cabang->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Dashboard/Cabang')
                ->where('ringkasan.total_wig', 1)
                ->where('ringkasan.total_lead', 1)
                ->where('ringkasan.on_track', 1)
                ->where('ringkasan.avg_pct', 120)
            );
    }

    public function test_lead_di_bawah_seratus_persen_tidak_dihitung_on_track(): void
    {
        $this->buatRealisasi(3, 1, 100, 50);

        $this->actingAs($this->admin())
            ->get("/dashboard-cabang?tahun={$this->tahun}&bulan=3&minggu=1&cabang_id={$this->cabang->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('ringkasan.on_track', 0)
                ->where('ringkasan.avg_pct', 50)
            );
    }

    public function test_data_bulanan_selalu_dua_belas_titik(): void
    {
        $this->buatRealisasi(2, 1, 100, 80);

        $this->actingAs($this->admin())
            ->get("/dashboard-cabang?tahun={$this->tahun}&cabang_id={$this->cabang->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('bulanData', 12)
                ->where('bulanData.1', 80)   // Februari
                ->where('bulanData.0', 0)    // Januari belum ada data
            );
    }

    public function test_progres_wig_dan_korelasi_dihitung(): void
    {
        WigTarget::create([
            'wig_id' => $this->wig->id,
            'cabang_id' => $this->cabang->id,
            'nilai_awal' => 0,
            'nilai_target' => 1000,
            'satuan' => 'orang',
        ]);

        foreach ([1, 2, 3] as $bulan) {
            WigRealisasi::create([
                'wig_id' => $this->wig->id,
                'cabang_id' => $this->cabang->id,
                'tahun' => $this->tahun,
                'bulan' => $bulan,
                'nilai' => 100 * $bulan,
            ]);
            $this->buatRealisasi($bulan, 1, 100, 50 * $bulan);
        }

        $this->actingAs($this->admin())
            ->get("/dashboard-cabang?tahun={$this->tahun}&bulan=3&cabang_id={$this->cabang->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('wigProgress', 1)
                ->has('wigProgress.0.wig_pct_bulan', 12)
                ->has('wigProgress.0.lead_pct_mingguan', 12 * LeadMeasureRealisasi::JUMLAH_MINGGU)
                ->has('wigProgress.0.korelasi')
            );
    }

    public function test_korelasi_kurang_data_bila_kurang_dari_tiga_bulan(): void
    {
        WigTarget::create([
            'wig_id' => $this->wig->id,
            'cabang_id' => $this->cabang->id,
            'nilai_awal' => 0,
            'nilai_target' => 1000,
            'satuan' => 'orang',
        ]);

        WigRealisasi::create([
            'wig_id' => $this->wig->id,
            'cabang_id' => $this->cabang->id,
            'tahun' => $this->tahun,
            'bulan' => 1,
            'nilai' => 100,
        ]);

        $this->actingAs($this->admin())
            ->get("/dashboard-cabang?tahun={$this->tahun}&cabang_id={$this->cabang->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('wigProgress.0.korelasi.level', 'kurang_data')
            );
    }

    public function test_kantor_cabang_terkunci_pada_cabangnya_sendiri(): void
    {
        $lain = Cabang::create([
            'kode' => 'KC-JKT', 'nama' => 'Cabang Jakarta', 'wilayah_id' => $this->wilayah->id,
        ]);

        $user = User::factory()->create([
            'wilayah_id' => $this->wilayah->id,
            'cabang_id' => $this->cabang->id,
        ])->assignRole('kantor_cabang');

        $this->actingAs($user)
            ->get("/dashboard-cabang?cabang_id={$lain->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filter.cabang_id', $this->cabang->id)
                ->where('terkunciCabang', true)
                ->has('cabangs', 1)
            );
    }

    public function test_pohon_wig_berisi_lag_dan_lead(): void
    {
        $this->buatRealisasi(3, 1, 100, 120);

        $this->actingAs($this->admin())
            ->get("/dashboard-cabang?tahun={$this->tahun}&bulan=3&minggu=1&cabang_id={$this->cabang->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('pohonWig', 1)
                ->has('pohonWig.0.lags', 1)
                ->has('pohonWig.0.lags.0.leads', 1)
                ->where('pohonWig.0.lags.0.leads.0.status', 'on')
                ->where('pohonWig.0.lags.0.leads.0.tren', 'naik')
            );
    }
}
