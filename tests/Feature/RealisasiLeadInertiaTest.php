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

class RealisasiLeadInertiaTest extends TestCase
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
            'bidang' => 'JPK',
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
            'kode_lead' => 'LEAD-JPK-BDG-01-'.$this->tahun,
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

    /** Satu paket isian untuk seluruh minggu dalam sebulan. */
    private function semuaMinggu(int $target = 100, int $realisasi = 80): array
    {
        $minggu = [];

        for ($m = 1; $m <= LeadMeasureRealisasi::JUMLAH_MINGGU; $m++) {
            $minggu[] = ['minggu_ke' => $m, 'target' => $target, 'realisasi' => $realisasi, 'keterangan' => null];
        }

        return $minggu;
    }

    public function test_grid_kosong_sebelum_wig_dan_cabang_dipilih(): void
    {
        $this->actingAs($this->admin())
            ->get('/realisasi')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Realisasi/Index')
                ->has('leads', 0)
            );
    }

    public function test_tiap_lead_punya_baris_untuk_semua_minggu(): void
    {
        $this->actingAs($this->admin())
            ->get("/realisasi?wig_id={$this->wig->id}&cabang_id={$this->cabang->id}&tahun={$this->tahun}&bulan=3")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('leads', 1)
                ->has('leads.0.minggu', LeadMeasureRealisasi::JUMLAH_MINGGU)
                ->where('leads.0.minggu.0.minggu_ke', 1)
                ->where('leads.0.minggu.4.minggu_ke', LeadMeasureRealisasi::JUMLAH_MINGGU)
            );
    }

    public function test_lead_nonaktif_tidak_ditampilkan(): void
    {
        $this->lead->update(['is_active' => false]);

        $this->actingAs($this->admin())
            ->get("/realisasi?wig_id={$this->wig->id}&cabang_id={$this->cabang->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page->has('leads', 0));
    }

    public function test_menyimpan_seluruh_minggu_sekaligus(): void
    {
        $this->actingAs($this->admin())
            ->post('/realisasi', [
                'lead_measure_id' => $this->lead->id,
                'cabang_id' => $this->cabang->id,
                'tahun' => $this->tahun,
                'bulan' => 3,
                'minggu' => $this->semuaMinggu(),
            ])
            ->assertSessionHas('success');

        $this->assertSame(
            LeadMeasureRealisasi::JUMLAH_MINGGU,
            LeadMeasureRealisasi::where('lead_measure_id', $this->lead->id)->count()
        );
    }

    public function test_persentase_dihitung_otomatis_saat_menyimpan(): void
    {
        $this->actingAs($this->admin())
            ->post('/realisasi', [
                'lead_measure_id' => $this->lead->id,
                'cabang_id' => $this->cabang->id,
                'tahun' => $this->tahun,
                'bulan' => 3,
                'minggu' => $this->semuaMinggu(200, 150),
            ]);

        $baris = LeadMeasureRealisasi::where('lead_measure_id', $this->lead->id)->first();

        $this->assertEquals(75, $baris->persentase);
    }

    public function test_menyimpan_ulang_memperbarui_baris_yang_sama(): void
    {
        $kirim = fn (int $realisasi) => $this->post('/realisasi', [
            'lead_measure_id' => $this->lead->id,
            'cabang_id' => $this->cabang->id,
            'tahun' => $this->tahun,
            'bulan' => 3,
            'minggu' => $this->semuaMinggu(100, $realisasi),
        ]);

        $this->actingAs($this->admin());
        $kirim(50);
        $kirim(90);

        $this->assertSame(
            LeadMeasureRealisasi::JUMLAH_MINGGU,
            LeadMeasureRealisasi::where('lead_measure_id', $this->lead->id)->count()
        );
        $this->assertEquals(90, LeadMeasureRealisasi::where('minggu_ke', 1)->first()->realisasi);
    }

    public function test_lead_yang_bukan_milik_cabang_ditolak(): void
    {
        $lain = Cabang::create([
            'kode' => 'KC-JKT', 'nama' => 'Cabang Jakarta', 'wilayah_id' => $this->wilayah->id,
        ]);

        $this->actingAs($this->admin())
            ->post('/realisasi', [
                'lead_measure_id' => $this->lead->id,
                'cabang_id' => $lain->id,
                'tahun' => $this->tahun,
                'bulan' => 3,
                'minggu' => $this->semuaMinggu(),
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, LeadMeasureRealisasi::count());
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
            ->post('/realisasi', [
                'lead_measure_id' => $this->lead->id,
                'cabang_id' => $lain->id,
                'tahun' => $this->tahun,
                'bulan' => 3,
                'minggu' => $this->semuaMinggu(),
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, LeadMeasureRealisasi::count());
    }

    public function test_kantor_cabang_terkunci_pada_cabangnya_sendiri(): void
    {
        $user = User::factory()->create([
            'wilayah_id' => $this->wilayah->id,
            'cabang_id' => $this->cabang->id,
        ])->assignRole('kantor_cabang');

        $this->actingAs($user)
            ->get("/realisasi?wig_id={$this->wig->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filter.cabang_id', $this->cabang->id)
                ->where('terkunciCabang', true)
            );
    }
}
