<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\LagMeasure;
use App\Models\LeadMeasure;
use App\Models\User;
use App\Models\Wig;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LagLeadInertiaTest extends TestCase
{
    use RefreshDatabase;

    private Wilayah $wilayah;

    private Cabang $cabang;

    private Wig $wig;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'kedeputian_wilayah', 'kantor_cabang'] as $role) {
            Role::findOrCreate($role);
        }

        $this->wilayah = Wilayah::create(['kode' => 'W01', 'nama' => 'Wilayah Satu']);
        $this->cabang = Cabang::create([
            'kode' => 'KC-BDG', 'nama' => 'Cabang Bandung', 'wilayah_id' => $this->wilayah->id,
        ]);
        $this->wig = Wig::create([
            'kode_wig' => 'WIG-01',
            'nama_wig' => 'WIG Pertama',
            'bidang' => 'Kepesertaan',
            'tahun' => (int) date('Y'),
            'wilayah_id' => $this->wilayah->id,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }

    private function buatLag(array $ubah = []): LagMeasure
    {
        return LagMeasure::create(array_merge([
            'kode_lag' => 'BDG-01-'.date('Y'),
            'wig_id' => $this->wig->id,
            'cabang_id' => $this->cabang->id,
            'nama_lag' => 'Lag Pertama',
            'tahun' => (int) date('Y'),
        ], $ubah));
    }

    /* ---------------- Lag ---------------- */

    public function test_daftar_lag_tampil(): void
    {
        $this->buatLag();

        $this->actingAs($this->admin())
            ->get('/lag-measures')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('LagMeasure/Index')
                ->has('lags.data', 1)
            );
    }

    public function test_filter_bidang_menyaring_lag(): void
    {
        $this->buatLag();

        $wigLain = Wig::create([
            'kode_wig' => 'WIG-JPK',
            'nama_wig' => 'WIG Bidang Lain',
            'bidang' => 'JPK',
            'tahun' => (int) date('Y'),
            'wilayah_id' => $this->wilayah->id,
        ]);

        $this->buatLag(['kode_lag' => 'BDG-09-'.date('Y'), 'wig_id' => $wigLain->id]);

        $this->actingAs($this->admin())
            ->get('/lag-measures?bidang=JPK')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('lags.data', 1)
                ->where('lags.data.0.kode_lag', 'BDG-09-'.date('Y'))
                ->where('lags.data.0.wig.bidang', 'JPK')
                ->where('filter.bidang', 'JPK')
            );
    }

    public function test_filter_cabang_menyaring_lag(): void
    {
        $this->buatLag();

        $cabangLain = Cabang::create([
            'kode' => 'KC-JKT', 'nama' => 'Cabang Jakarta', 'wilayah_id' => $this->wilayah->id,
        ]);

        $this->buatLag(['kode_lag' => 'JKT-01-'.date('Y'), 'cabang_id' => $cabangLain->id]);

        $this->actingAs($this->admin())
            ->get("/lag-measures?cabang_id={$cabangLain->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('lags.data', 1)
                ->where('lags.data.0.kode_lag', 'JKT-01-'.date('Y'))
                ->where('filter.cabang_id', $cabangLain->id)
            );
    }

    public function test_filter_cabang_di_luar_wilayah_diabaikan(): void
    {
        $this->buatLag();

        $wilayahLain = Wilayah::create(['kode' => 'W02', 'nama' => 'Wilayah Dua']);
        $cabangLuar = Cabang::create([
            'kode' => 'KC-SBY', 'nama' => 'Cabang Surabaya', 'wilayah_id' => $wilayahLain->id,
        ]);

        $user = User::factory()->create(['wilayah_id' => $this->wilayah->id])->assignRole('kedeputian_wilayah');

        $this->actingAs($user)
            ->get("/lag-measures?cabang_id={$cabangLuar->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('lags.data', 1)
                ->where('filter.cabang_id', null)
            );
    }

    public function test_bidang_tidak_dikenal_diabaikan_pada_lag(): void
    {
        $this->buatLag();

        $this->actingAs($this->admin())
            ->get('/lag-measures?bidang=TIDAK-ADA')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('lags.data', 1)
                ->where('filter.bidang', null)
            );
    }

    public function test_kode_lag_diusulkan_dari_kode_cabang_dan_tahun(): void
    {
        $tahun = (int) date('Y');

        $this->actingAs($this->admin())
            ->getJson("/lag-measures/kode?cabang_id={$this->cabang->id}&tahun={$tahun}")
            ->assertOk()
            ->assertJson(['kode' => "BDG-01-{$tahun}"]);
    }

    public function test_usulan_kode_lag_melompati_kode_yang_sudah_dipakai(): void
    {
        $tahun = (int) date('Y');
        $this->buatLag();

        $this->actingAs($this->admin())
            ->getJson("/lag-measures/kode?cabang_id={$this->cabang->id}&tahun={$tahun}")
            ->assertOk()
            ->assertJson(['kode' => "BDG-02-{$tahun}"]);
    }

    public function test_menyimpan_lag(): void
    {
        $this->actingAs($this->admin())
            ->post('/lag-measures', [
                'wig_id' => $this->wig->id,
                'cabang_id' => $this->cabang->id,
                'kode_lag' => 'BDG-09-'.date('Y'),
                'nama_lag' => 'Lag Baru',
                'tahun' => (int) date('Y'),
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('lag_measures', ['kode_lag' => 'BDG-09-'.date('Y')]);
    }

    public function test_pesan_penolakan_hapus_sampai_ke_halaman(): void
    {
        $lag = $this->buatLag();
        LeadMeasure::create([
            'kode_lead' => 'LEAD-KEPESERTAAN-BDG-01-'.date('Y'),
            'lag_measure_id' => $lag->id,
            'wig_id' => $this->wig->id,
            'cabang_id' => $this->cabang->id,
            'nama_lead' => 'Lead Pertama',
            'tahun' => (int) date('Y'),
        ]);

        $this->actingAs($this->admin())
            ->delete("/lag-measures/{$lag->id}")
            ->assertRedirect();

        // Pesan harus ikut terkirim sebagai prop supaya bisa ditampilkan sebagai toast.
        $this->actingAs($this->admin())
            ->get('/lag-measures')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('flash.error', 'Lag Measure tidak dapat dihapus karena masih memiliki Lead Measure.')
            );
    }

    public function test_lag_tanpa_lead_bisa_dihapus(): void
    {
        $lag = $this->buatLag();

        $this->actingAs($this->admin())
            ->delete("/lag-measures/{$lag->id}")
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('lag_measures', ['id' => $lag->id]);
    }

    public function test_lag_dengan_lead_tidak_bisa_dihapus(): void
    {
        $lag = $this->buatLag();
        LeadMeasure::create([
            'kode_lead' => 'LEAD-KEPESERTAAN-BDG-01-'.date('Y'),
            'lag_measure_id' => $lag->id,
            'wig_id' => $this->wig->id,
            'cabang_id' => $this->cabang->id,
            'nama_lead' => 'Lead Pertama',
            'tahun' => (int) date('Y'),
        ]);

        $this->actingAs($this->admin())
            ->delete("/lag-measures/{$lag->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('lag_measures', ['id' => $lag->id]);
    }

    public function test_kantor_cabang_ditolak_membuka_lag(): void
    {
        $user = User::factory()->create()->assignRole('kantor_cabang');

        $this->actingAs($user)->get('/lag-measures')->assertForbidden();
    }

    /* ---------------- Lead ---------------- */

    private function buatLead(string $kode, ?LagMeasure $lag = null, array $ubah = []): LeadMeasure
    {
        return LeadMeasure::create(array_merge([
            'kode_lead' => $kode,
            'lag_measure_id' => ($lag ?? $this->buatLag())->id,
            'wig_id' => $this->wig->id,
            'cabang_id' => $this->cabang->id,
            'nama_lead' => 'Lead '.$kode,
            'is_active' => true,
            'tahun' => (int) date('Y'),
        ], $ubah));
    }

    public function test_semua_lead_tampil_tanpa_penyaringan(): void
    {
        $lag = $this->buatLag();
        $this->buatLead('LEAD-A', $lag);
        $this->buatLead('LEAD-B', $lag);

        $this->actingAs($this->admin())
            ->get('/lead-measures')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('LeadMeasure/Index')
                ->has('leads.data', 2)
                ->where('filter.wig_id', null)
                ->where('filter.lag_id', null)
            );
    }

    public function test_pilihan_lag_kosong_sebelum_wig_dan_cabang_dipilih(): void
    {
        $this->buatLead('LEAD-A', $this->buatLag());

        // Hanya WIG yang dipilih — Lag belum ditawarkan.
        $this->actingAs($this->admin())
            ->get("/lead-measures?wig_id={$this->wig->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('lags', 0));
    }

    public function test_pilihan_lag_muncul_setelah_wig_dan_cabang_dipilih(): void
    {
        $lag = $this->buatLag();
        $this->buatLead('LEAD-A', $lag);

        $this->actingAs($this->admin())
            ->get("/lead-measures?wig_id={$this->wig->id}&cabang_id={$this->cabang->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('lags', 1)
                ->where('lags.0.kode_lag', $lag->kode_lag)
            );
    }

    public function test_pilihan_lag_mengikuti_cabang_yang_dipilih(): void
    {
        $lagBandung = $this->buatLag();

        $cabangLain = Cabang::create([
            'kode' => 'KC-JKT', 'nama' => 'Cabang Jakarta', 'wilayah_id' => $this->wilayah->id,
        ]);

        $this->buatLag(['kode_lag' => 'JKT-01-'.date('Y'), 'cabang_id' => $cabangLain->id]);

        $this->actingAs($this->admin())
            ->get("/lead-measures?wig_id={$this->wig->id}&cabang_id={$this->cabang->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('lags', 1)
                ->where('lags.0.kode_lag', $lagBandung->kode_lag)
            );
    }

    public function test_filter_lag_menyaring_lead(): void
    {
        $lagA = $this->buatLag();
        $lagB = $this->buatLag(['kode_lag' => 'BDG-02-'.date('Y')]);

        $this->buatLead('LEAD-A', $lagA);
        $this->buatLead('LEAD-B', $lagB);

        $this->actingAs($this->admin())
            ->get("/lead-measures?wig_id={$this->wig->id}&cabang_id={$this->cabang->id}&lag_id={$lagB->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('leads.data', 1)
                ->where('leads.data.0.kode_lead', 'LEAD-B')
                ->where('filter.lag_id', $lagB->id)
            );
    }

    public function test_lag_di_luar_daftar_diabaikan_sebagai_filter(): void
    {
        $this->buatLead('LEAD-A');

        $this->actingAs($this->admin())
            ->get("/lead-measures?wig_id={$this->wig->id}&cabang_id={$this->cabang->id}&lag_id=99999")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('leads.data', 1)
                ->where('filter.lag_id', null)
            );
    }

    public function test_bidang_ikut_terkirim_untuk_kolom_tabel(): void
    {
        $this->buatLead('LEAD-A');

        $this->actingAs($this->admin())
            ->get('/lead-measures')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('leads.data.0.wig.bidang', 'Kepesertaan')
            );
    }

    public function test_daftar_lead_terisi_setelah_konteks_dipilih(): void
    {
        $lag = $this->buatLag();
        LeadMeasure::create([
            'kode_lead' => 'LEAD-KEPESERTAAN-BDG-01-'.date('Y'),
            'lag_measure_id' => $lag->id,
            'wig_id' => $this->wig->id,
            'cabang_id' => $this->cabang->id,
            'nama_lead' => 'Lead Pertama',
            'tahun' => (int) date('Y'),
        ]);

        $this->actingAs($this->admin())
            ->get("/lead-measures?wig_id={$this->wig->id}&cabang_id={$this->cabang->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('leads.data', 1)
                ->where('leads.data.0.kode_lead', 'LEAD-KEPESERTAAN-BDG-01-'.date('Y'))
            );
    }

    public function test_lead_aktif_diurutkan_lebih_dulu(): void
    {
        $lag = $this->buatLag();

        $buat = fn (string $kode, bool $aktif) => LeadMeasure::create([
            'kode_lead' => $kode,
            'lag_measure_id' => $lag->id,
            'wig_id' => $this->wig->id,
            'cabang_id' => $this->cabang->id,
            'nama_lead' => 'Lead '.$kode,
            'is_active' => $aktif,
            'tahun' => (int) date('Y'),
        ]);

        // Kode A nonaktif sengaja dibuat lebih dulu secara abjad.
        $buat('LEAD-A', false);
        $buat('LEAD-B', true);

        $this->actingAs($this->admin())
            ->get("/lead-measures?wig_id={$this->wig->id}&cabang_id={$this->cabang->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('leads.data', 2)
                ->where('leads.data.0.kode_lead', 'LEAD-B')
                ->where('leads.data.1.kode_lead', 'LEAD-A')
            );
    }

    public function test_konteks_memberi_kode_dan_daftar_lag(): void
    {
        $tahun = (int) date('Y');
        $this->buatLag();

        $this->actingAs($this->admin())
            ->getJson("/lead-measures/konteks?wig_id={$this->wig->id}&cabang_id={$this->cabang->id}&tahun={$tahun}")
            ->assertOk()
            ->assertJson(['kode' => "LEAD-KEPESERTAAN-BDG-01-{$tahun}"])
            ->assertJsonCount(1, 'lags')
            ->assertJsonPath('lags.0.kode_lag', 'BDG-01-'.$tahun);
    }

    public function test_konteks_kosong_bila_wig_atau_cabang_belum_dipilih(): void
    {
        $this->actingAs($this->admin())
            ->getJson("/lead-measures/konteks?wig_id={$this->wig->id}")
            ->assertOk()
            ->assertJson(['kode' => '', 'lags' => []]);
    }

    public function test_konteks_hanya_memuat_lag_milik_wig_dan_cabang_itu(): void
    {
        $this->buatLag();

        $wigLain = Wig::create([
            'kode_wig' => 'WIG-02',
            'nama_wig' => 'WIG Kedua',
            'bidang' => 'JPK',
            'tahun' => (int) date('Y'),
            'wilayah_id' => $this->wilayah->id,
        ]);

        LagMeasure::create([
            'kode_lag' => 'BDG-99-'.date('Y'),
            'wig_id' => $wigLain->id,
            'cabang_id' => $this->cabang->id,
            'nama_lag' => 'Lag WIG Lain',
            'tahun' => (int) date('Y'),
        ]);

        $this->actingAs($this->admin())
            ->getJson("/lead-measures/konteks?wig_id={$this->wig->id}&cabang_id={$this->cabang->id}")
            ->assertOk()
            ->assertJsonCount(1, 'lags')
            ->assertJsonPath('lags.0.kode_lag', 'BDG-01-'.date('Y'));
    }

    public function test_toggle_membalik_status_aktif(): void
    {
        $lag = $this->buatLag();
        $lead = LeadMeasure::create([
            'kode_lead' => 'LEAD-KEPESERTAAN-BDG-01-'.date('Y'),
            'lag_measure_id' => $lag->id,
            'wig_id' => $this->wig->id,
            'cabang_id' => $this->cabang->id,
            'nama_lead' => 'Lead Pertama',
            'is_active' => true,
            'tahun' => (int) date('Y'),
        ]);

        $this->actingAs($this->admin())
            ->patch("/lead-measures/{$lead->id}/toggle")
            ->assertSessionHas('success');

        $this->assertFalse($lead->fresh()->is_active);
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

        // Walau meminta cabang lain lewat query string, server tetap memakai cabangnya sendiri.
        $this->actingAs($user)
            ->get("/lead-measures?wig_id={$this->wig->id}&cabang_id={$lain->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filter.cabang_id', $this->cabang->id)
                ->where('terkunciCabang', true)
            );
    }
}
