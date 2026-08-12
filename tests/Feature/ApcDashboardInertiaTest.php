<?php

namespace Tests\Feature;

use App\Models\MonevApcUpload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApcDashboardInertiaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'kedeputian_wilayah', 'kantor_cabang'] as $role) {
            Role::findOrCreate($role);
        }

        Storage::fake('local');
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }

    private function cabang(): User
    {
        return User::factory()->create()->assignRole('kantor_cabang');
    }

    private function berkasCsv(string $isi = "Kolom A,Kolom B\nNilai 1,Nilai 2\n"): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('data-apc.csv', $isi);
    }

    private function buatUnggahan(string $indikator = 'total'): MonevApcUpload
    {
        return MonevApcUpload::create([
            'indikator' => $indikator,
            'tahun' => (int) date('Y'),
            'original_name' => 'lama.xlsx',
            'file_path' => 'apc-uploads/lama.xlsx',
            'sheet_json' => [['Kolom A', 'Kolom B'], ['1', '2']],
            'rows_count' => 1,
            'uploaded_by' => $this->admin()->id,
        ]);
    }

    public function test_halaman_render_untuk_indikator_valid(): void
    {
        $this->actingAs($this->admin())
            ->get('/monitoring-kinerja/peserta-aktif')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('MonitoringKinerja/Apc')
                ->where('indikator', 'peserta-aktif')
                ->where('label', 'APC Peserta Aktif')
                ->has('daftarIndikator', 6)
                ->where('terbaru', null)
            );
    }

    public function test_indikator_tidak_dikenal_menghasilkan_404(): void
    {
        $this->actingAs($this->admin())
            ->get('/monitoring-kinerja/entah-apa')
            ->assertNotFound();
    }

    public function test_kantor_cabang_bisa_melihat_tapi_tidak_bisa_mengelola(): void
    {
        $this->actingAs($this->cabang())
            ->get('/monitoring-kinerja/total')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('bisaKelola', false));
    }

    public function test_admin_bisa_mengunggah_berkas(): void
    {
        $this->actingAs($this->admin())
            ->post('/monitoring-kinerja/total/upload', [
                'file' => $this->berkasCsv(),
                'tahun' => 2026,
            ])
            ->assertSessionHas('success');

        $unggahan = MonevApcUpload::first();

        $this->assertNotNull($unggahan);
        $this->assertSame('total', $unggahan->indikator);
        $this->assertSame(2026, $unggahan->tahun);
        $this->assertSame(1, $unggahan->rows_count);
        Storage::assertExists($unggahan->file_path);
    }

    public function test_berkas_selain_excel_ditolak(): void
    {
        $this->actingAs($this->admin())
            ->post('/monitoring-kinerja/total/upload', [
                'file' => UploadedFile::fake()->create('gambar.jpg', 10),
            ])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, MonevApcUpload::count());
    }

    public function test_berkas_lebih_dari_sepuluh_mb_ditolak(): void
    {
        $this->actingAs($this->admin())
            ->post('/monitoring-kinerja/total/upload', [
                'file' => UploadedFile::fake()->create('besar.xlsx', 11000),
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_kantor_cabang_tidak_bisa_mengunggah(): void
    {
        $this->actingAs($this->cabang())
            ->post('/monitoring-kinerja/total/upload', ['file' => $this->berkasCsv()])
            ->assertSessionHas('error');

        $this->assertSame(0, MonevApcUpload::count());
    }

    public function test_data_terbaru_ditampilkan_dengan_barisnya(): void
    {
        $this->buatUnggahan();

        $this->actingAs($this->admin())
            ->get('/monitoring-kinerja/total')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('terbaru.original_name', 'lama.xlsx')
                ->has('terbaru.baris', 2)
                ->has('riwayat', 1)
            );
    }

    public function test_unggahan_indikator_lain_tidak_bocor(): void
    {
        $this->buatUnggahan('kepuasan');

        $this->actingAs($this->admin())
            ->get('/monitoring-kinerja/total')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('terbaru', null)
                ->has('riwayat', 0)
            );
    }

    public function test_menghapus_unggahan_dari_indikator_lain_menghasilkan_404(): void
    {
        $unggahan = $this->buatUnggahan('kepuasan');

        $this->actingAs($this->admin())
            ->delete("/monitoring-kinerja/total/{$unggahan->id}")
            ->assertNotFound();

        $this->assertSame(1, MonevApcUpload::count());
    }

    public function test_admin_bisa_menghapus_unggahan(): void
    {
        $unggahan = $this->buatUnggahan();

        $this->actingAs($this->admin())
            ->delete("/monitoring-kinerja/total/{$unggahan->id}")
            ->assertSessionHas('success');

        $this->assertSame(0, MonevApcUpload::count());
    }

    public function test_kantor_cabang_tidak_bisa_menghapus(): void
    {
        $unggahan = $this->buatUnggahan();

        $this->actingAs($this->cabang())
            ->delete("/monitoring-kinerja/total/{$unggahan->id}")
            ->assertSessionHas('error');

        $this->assertSame(1, MonevApcUpload::count());
    }
}
