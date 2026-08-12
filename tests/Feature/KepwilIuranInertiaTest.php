<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\IuranMonitoring;
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

class KepwilIuranInertiaTest extends TestCase
{
    use RefreshDatabase;

    private Wilayah $wilayah;

    private Cabang $cabang;

    private Wig $wig;

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
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }

    private function buatLead(Cabang $cabang): LeadMeasure
    {
        $lag = LagMeasure::create([
            'kode_lag' => 'LAG-'.$cabang->id,
            'wig_id' => $this->wig->id,
            'cabang_id' => $cabang->id,
            'nama_lag' => 'Lag',
            'tahun' => $this->tahun,
        ]);

        return LeadMeasure::create([
            'kode_lead' => 'LEAD-'.$cabang->id,
            'lag_measure_id' => $lag->id,
            'wig_id' => $this->wig->id,
            'cabang_id' => $cabang->id,
            'nama_lead' => 'Lead',
            'is_active' => true,
            'tahun' => $this->tahun,
        ]);
    }

    private function buatRealisasi(LeadMeasure $lead, int $target, int $realisasi, int $bulan = 3, int $minggu = 1): void
    {
        LeadMeasureRealisasi::create([
            'lead_measure_id' => $lead->id,
            'cabang_id' => $lead->cabang_id,
            'tahun' => $this->tahun,
            'bulan' => $bulan,
            'minggu_ke' => $minggu,
            'target' => $target,
            'realisasi' => $realisasi,
        ]);
    }

    /* ---------------- Dashboard Kepwil ---------------- */

    public function test_peringkat_diurutkan_dari_capaian_tertinggi(): void
    {
        $lain = Cabang::create([
            'kode' => 'KC-JKT', 'nama' => 'Cabang Jakarta', 'wilayah_id' => $this->wilayah->id,
        ]);

        $this->buatRealisasi($this->buatLead($this->cabang), 100, 50);
        $this->buatRealisasi($this->buatLead($lain), 100, 120);

        $this->actingAs($this->admin())
            ->get("/dashboard-kepwil?tahun={$this->tahun}&bulan=3&minggu=1")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Dashboard/Kepwil')
                ->has('peringkat', 2)
                ->where('peringkat.0.nama', 'Cabang Jakarta')
                ->where('peringkat.0.pct', 120)
                ->where('peringkat.0.status', 'on')
                ->where('peringkat.1.status', 'awas')
            );
    }

    public function test_status_waspada_pada_rentang_sembilan_puluh(): void
    {
        $this->buatRealisasi($this->buatLead($this->cabang), 100, 95);

        $this->actingAs($this->admin())
            ->get("/dashboard-kepwil?tahun={$this->tahun}&bulan=3&minggu=1")
            ->assertInertia(fn (AssertableInertia $page) => $page->where('peringkat.0.status', 'waspada'));
    }

    public function test_cabang_teratas_dipilih_otomatis(): void
    {
        $lead = $this->buatLead($this->cabang);
        $this->buatRealisasi($lead, 100, 80);

        $this->actingAs($this->admin())
            ->get("/dashboard-kepwil?tahun={$this->tahun}&bulan=3&minggu=1")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filter.cabang_id', $this->cabang->id)
                ->has('detailLead', 1)
                ->where('detailLead.0.pct', 80)
            );
    }

    public function test_tanpa_pembanding_detail_kedua_kosong(): void
    {
        $this->buatRealisasi($this->buatLead($this->cabang), 100, 80);

        $this->actingAs($this->admin())
            ->get("/dashboard-kepwil?tahun={$this->tahun}&bulan=3&minggu=1")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('detailLeadBanding', null)
                ->where('filter.minggu_banding', null)
            );
    }

    public function test_membandingkan_dua_minggu(): void
    {
        $lead = $this->buatLead($this->cabang);
        $this->buatRealisasi($lead, 100, 40, 3, 3);
        $this->buatRealisasi($lead, 100, 145, 3, 4);

        $this->actingAs($this->admin())
            ->get("/dashboard-kepwil?tahun={$this->tahun}&bulan=3&minggu=4&minggu_banding=3")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('detailLead', 1)
                ->has('detailLeadBanding', 1)
                ->where('detailLead.0.pct', 145)
                ->where('detailLead.0.status', 'on')
                ->where('detailLeadBanding.0.pct', 40)
                ->where('detailLeadBanding.0.status', 'awas')
            );
    }

    public function test_minggu_pembanding_sama_dengan_minggu_utama_diabaikan(): void
    {
        $this->buatRealisasi($this->buatLead($this->cabang), 100, 80);

        $this->actingAs($this->admin())
            ->get("/dashboard-kepwil?tahun={$this->tahun}&bulan=3&minggu=1&minggu_banding=1")
            ->assertInertia(fn (AssertableInertia $page) => $page->where('detailLeadBanding', null));
    }

    public function test_filter_lag_menyaring_lead(): void
    {
        $leadA = $this->buatLead($this->cabang);
        $this->buatRealisasi($leadA, 100, 80);

        // Lead kedua di bawah Lag berbeda pada WIG yang sama.
        $lagLain = LagMeasure::create([
            'kode_lag' => 'LAG-LAIN',
            'wig_id' => $this->wig->id,
            'cabang_id' => $this->cabang->id,
            'nama_lag' => 'Lag Lain',
            'tahun' => $this->tahun,
        ]);

        $leadB = LeadMeasure::create([
            'kode_lead' => 'LEAD-LAIN',
            'lag_measure_id' => $lagLain->id,
            'wig_id' => $this->wig->id,
            'cabang_id' => $this->cabang->id,
            'nama_lead' => 'Lead Lain',
            'is_active' => true,
            'tahun' => $this->tahun,
        ]);

        $this->buatRealisasi($leadB, 100, 20);

        $this->actingAs($this->admin())
            ->get("/dashboard-kepwil?tahun={$this->tahun}&bulan=3&minggu=1&wig_id={$this->wig->id}&lag_id={$lagLain->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('detailLead', 1)
                ->where('detailLead.0.kode_lead', 'LEAD-LAIN')
                ->has('lags', 2)
                ->where('sasaran.lag.kode', 'LAG-LAIN')
                ->has('sasaran.leads', 1)
            );
    }

    public function test_sasaran_kosong_bila_wig_belum_dipilih(): void
    {
        $this->buatRealisasi($this->buatLead($this->cabang), 100, 80);

        $this->actingAs($this->admin())
            ->get("/dashboard-kepwil?tahun={$this->tahun}&bulan=3&minggu=1")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('sasaran', null)
                ->has('lags', 0)
            );
    }

    public function test_kedeputian_wilayah_hanya_melihat_cabang_wilayahnya(): void
    {
        $lainWilayah = Wilayah::create(['kode' => 'W02', 'nama' => 'Wilayah Dua']);
        Cabang::create(['kode' => 'KC-SBY', 'nama' => 'Cabang Surabaya', 'wilayah_id' => $lainWilayah->id]);

        $user = User::factory()->create(['wilayah_id' => $this->wilayah->id])->assignRole('kedeputian_wilayah');

        $this->actingAs($user)
            ->get('/dashboard-kepwil')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('cabangs', 1)
                ->where('cabangs.0.nama', 'Cabang Bandung')
            );
    }

    /* ---------------- Monitoring Iuran ---------------- */

    private function isiIuran(array $ubah = []): array
    {
        return array_merge([
            'cabang_id' => $this->cabang->id,
            'tahun' => $this->tahun,
            'bulan' => 3,
            'minggu' => 1,
            'nama_pemda' => 'Pemda Satu',
            'tagihan' => 1000,
            'status_bayar' => 'belum',
            'outstanding' => 0,
            'pic' => null,
            'kendala' => null,
            'keterangan' => null,
            'target_penyelesaian' => null,
        ], $ubah);
    }

    public function test_menyimpan_data_iuran(): void
    {
        $this->actingAs($this->admin())
            ->post('/monitoring-prioritas/iuran', $this->isiIuran())
            ->assertSessionHas('success');

        $this->assertDatabaseHas('iuran_monitorings', ['nama_pemda' => 'Pemda Satu', 'no_urut' => 1]);
    }

    public function test_outstanding_mengikuti_status_bayar(): void
    {
        $this->actingAs($this->admin());

        // Belum bayar → outstanding sebesar tagihan walau dikirim nol.
        $this->post('/monitoring-prioritas/iuran', $this->isiIuran(['outstanding' => 0]));
        $this->assertEquals(1000, IuranMonitoring::first()->outstanding);

        // Sudah bayar → outstanding nol walau dikirim besar.
        $this->post('/monitoring-prioritas/iuran', $this->isiIuran([
            'nama_pemda' => 'Pemda Dua',
            'status_bayar' => 'sudah',
            'outstanding' => 500,
        ]));
        $this->assertEquals(0, IuranMonitoring::where('nama_pemda', 'Pemda Dua')->first()->outstanding);
    }

    public function test_nama_pemda_tidak_boleh_duplikat_di_minggu_sama(): void
    {
        $this->actingAs($this->admin());
        $this->post('/monitoring-prioritas/iuran', $this->isiIuran());

        $this->post('/monitoring-prioritas/iuran', $this->isiIuran(['nama_pemda' => 'pemda satu']))
            ->assertSessionHasErrors('nama_pemda');

        $this->assertSame(1, IuranMonitoring::count());
    }

    public function test_maksimal_tiga_pemda_per_minggu(): void
    {
        $this->actingAs($this->admin());

        foreach (['A', 'B', 'C'] as $nama) {
            $this->post('/monitoring-prioritas/iuran', $this->isiIuran(['nama_pemda' => "Pemda {$nama}"]));
        }

        $this->post('/monitoring-prioritas/iuran', $this->isiIuran(['nama_pemda' => 'Pemda D']))
            ->assertSessionHasErrors('nama_pemda');

        $this->assertSame(3, IuranMonitoring::count());
    }

    public function test_pemda_sama_boleh_di_minggu_berbeda(): void
    {
        $this->actingAs($this->admin());
        $this->post('/monitoring-prioritas/iuran', $this->isiIuran());

        $this->post('/monitoring-prioritas/iuran', $this->isiIuran(['minggu' => 2]))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, IuranMonitoring::count());
    }

    public function test_filter_status_menyaring_hasil(): void
    {
        $this->actingAs($this->admin());
        $this->post('/monitoring-prioritas/iuran', $this->isiIuran(['nama_pemda' => 'Pemda A']));
        $this->post('/monitoring-prioritas/iuran', $this->isiIuran([
            'nama_pemda' => 'Pemda B',
            'status_bayar' => 'sudah',
        ]));

        $this->get("/monitoring-prioritas/iuran?tahun={$this->tahun}&bulan=3&status=sudah")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('items', 1)
                ->where('items.0.nama_pemda', 'Pemda B')
            );
    }

    public function test_kantor_cabang_tidak_bisa_menulis_untuk_cabang_lain(): void
    {
        $lain = Cabang::create([
            'kode' => 'KC-JKT', 'nama' => 'Cabang Jakarta', 'wilayah_id' => $this->wilayah->id,
        ]);

        $user = User::factory()->create([
            'wilayah_id' => $this->wilayah->id,
            'cabang_id' => $this->cabang->id,
        ])->assignRole('kantor_cabang');

        $this->actingAs($user)
            ->post('/monitoring-prioritas/iuran', $this->isiIuran(['cabang_id' => $lain->id]))
            ->assertSessionHasErrors('cabang_id');

        $this->assertSame(0, IuranMonitoring::count());
    }

    public function test_menghapus_data_iuran(): void
    {
        $this->actingAs($this->admin());
        $this->post('/monitoring-prioritas/iuran', $this->isiIuran());
        $item = IuranMonitoring::first();

        $this->delete("/monitoring-prioritas/iuran/{$item->id}")->assertSessionHas('success');

        $this->assertSame(0, IuranMonitoring::count());
    }
}
