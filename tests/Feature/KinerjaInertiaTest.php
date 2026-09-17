<?php

namespace Tests\Feature;

use App\Models\Kinerja\FileCapaian;
use App\Models\Kinerja\Indikator;
use App\Models\Kinerja\Kategori;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class KinerjaInertiaTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        Permission::findOrCreate('akses-kinerja');
        Role::findOrCreate($role)->givePermissionTo('akses-kinerja');

        return User::factory()->create()->assignRole($role);
    }

    private function indikator(): Indikator
    {
        $kategori = Kategori::create(['nama' => 'Kepesertaan']);

        return Indikator::create([
            'kinerja_kategori_id' => $kategori->id,
            'nama' => 'Peserta Aktif',
        ]);
    }

    public function test_admin_bisa_melihat_daftar_kategori(): void
    {
        Kategori::create(['nama' => 'Kepesertaan']);

        $this->actingAs($this->user('admin'))
            ->get('/kinerja/kategori')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Kinerja/Kategori')
                ->has('kategoris.data', 1)
                ->where('kategoris.data.0.nama', 'Kepesertaan')
            );
    }

    public function test_kantor_cabang_tidak_boleh_membuka_kategori(): void
    {
        $this->actingAs($this->user('kantor_cabang'))
            ->get('/kinerja/kategori')
            ->assertForbidden();
    }

    public function test_indikator_hanya_bisa_dibuat_di_kategori_yang_ada(): void
    {
        $kategori = Kategori::create(['nama' => 'Keuangan']);

        $this->actingAs($this->user('kedeputian_wilayah'))
            ->post('/kinerja/indikator', [
                'kinerja_kategori_id' => $kategori->id,
                'nama' => 'Penerimaan Iuran',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('kinerja_indikators', [
            'kinerja_kategori_id' => $kategori->id,
            'nama' => 'Penerimaan Iuran',
        ]);
    }

    public function test_upload_file_capaian_tersimpan(): void
    {
        Storage::fake();
        $indikator = $this->indikator();

        $this->actingAs($this->user('admin'))
            ->post('/kinerja/file', [
                'kinerja_kategori_id' => $indikator->kinerja_kategori_id,
                'kinerja_indikator_id' => $indikator->id,
                'nama' => 'Laporan Agustus',
                'file' => UploadedFile::fake()->create('laporan.pdf', 100, 'application/pdf'),
                'keterangan' => 'Rekap bulanan',
                'bulan' => 8,
                'tahun' => 2026,
            ])
            ->assertSessionHasNoErrors();

        $file = FileCapaian::firstOrFail();

        $this->assertSame('Laporan Agustus', $file->nama);
        $this->assertSame(8, $file->bulan);
        Storage::assertExists($file->file_path);
    }

    public function test_file_lebih_dari_2mb_ditolak(): void
    {
        Storage::fake();
        $indikator = $this->indikator();

        $this->actingAs($this->user('admin'))
            ->post('/kinerja/file', [
                'kinerja_kategori_id' => $indikator->kinerja_kategori_id,
                'kinerja_indikator_id' => $indikator->id,
                'nama' => 'Terlalu Besar',
                'file' => UploadedFile::fake()->create('besar.pdf', 3000, 'application/pdf'),
                'bulan' => 8,
                'tahun' => 2026,
            ])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, FileCapaian::count());
    }

    /** Indikator yang bukan milik kategori terpilih tidak boleh lolos. */
    public function test_indikator_harus_milik_kategori_yang_dipilih(): void
    {
        Storage::fake();
        $indikator = $this->indikator();
        $kategoriLain = Kategori::create(['nama' => 'Keuangan']);

        $this->actingAs($this->user('admin'))
            ->post('/kinerja/file', [
                'kinerja_kategori_id' => $kategoriLain->id,
                'kinerja_indikator_id' => $indikator->id,
                'nama' => 'Salah Pasang',
                'file' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
                'bulan' => 8,
                'tahun' => 2026,
            ])
            ->assertSessionHasErrors('kinerja_indikator_id');
    }

    public function test_kantor_cabang_bisa_melihat_dan_mengunduh_file(): void
    {
        Storage::fake();
        $indikator = $this->indikator();

        $file = FileCapaian::create([
            'kinerja_indikator_id' => $indikator->id,
            'nama' => 'Laporan Agustus',
            'file_path' => UploadedFile::fake()->create('laporan.pdf', 10)->store('kinerja-files'),
            'nama_asli' => 'laporan.pdf',
            'ukuran' => 10240,
            'bulan' => 8,
            'tahun' => (int) date('Y'),
        ]);

        $cabang = $this->user('kantor_cabang');

        $this->actingAs($cabang)
            ->get('/kinerja/file')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Kinerja/File')
                ->where('jumlahFile', 1)
                ->has('kelompok', 1)
                ->where('kelompok.0.nama', 'Kepesertaan')
                ->has('kelompok.0.indikators', 1)
                ->has('kelompok.0.indikators.0.files', 1)
                ->where('kelompok.0.indikators.0.files.0.nama', 'Laporan Agustus')
                ->where('bisaKelola', false)
            );

        $this->actingAs($cabang)->get("/kinerja/file/{$file->id}/unduh")->assertOk();
    }

    public function test_kantor_cabang_tidak_boleh_mengunggah(): void
    {
        Storage::fake();
        $indikator = $this->indikator();

        $this->actingAs($this->user('kantor_cabang'))
            ->post('/kinerja/file', [
                'kinerja_kategori_id' => $indikator->kinerja_kategori_id,
                'kinerja_indikator_id' => $indikator->id,
                'nama' => 'Coba',
                'file' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
                'bulan' => 8,
                'tahun' => 2026,
            ])
            ->assertForbidden();
    }

    /** Menghapus kategori berisi indikator akan ikut melenyapkan berkasnya. */
    public function test_kategori_berisi_indikator_tidak_bisa_dihapus(): void
    {
        $indikator = $this->indikator();

        $this->actingAs($this->user('admin'))
            ->delete("/kinerja/kategori/{$indikator->kinerja_kategori_id}")
            ->assertSessionHas('error');

        $this->assertDatabaseCount('kinerja_kategoris', 1);
    }
}
