<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WilayahInertiaTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('admin');

        return User::factory()->create()->assignRole('admin');
    }

    public function test_admin_bisa_melihat_daftar_wilayah(): void
    {
        Wilayah::create(['kode' => 'W01', 'nama' => 'Wilayah Satu']);

        $this->actingAs($this->admin())
            ->get('/wilayahs')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Wilayah/Index')
                ->has('wilayahs.data', 1)
                ->where('wilayahs.data.0.nama', 'Wilayah Satu')
            );
    }

    public function test_pencarian_menyaring_hasil(): void
    {
        Wilayah::create(['kode' => 'W01', 'nama' => 'Jawa Barat']);
        Wilayah::create(['kode' => 'W02', 'nama' => 'Sumatera Utara']);

        $this->actingAs($this->admin())
            ->get('/wilayahs?cari=Sumatera')
            ->assertOk()
            ->assertSee('Sumatera Utara')
            ->assertDontSee('Jawa Barat');
    }

    public function test_admin_bisa_menyimpan_wilayah(): void
    {
        $this->actingAs($this->admin())
            ->post('/wilayahs', ['kode' => 'W09', 'nama' => 'Wilayah Baru', 'deskripsi' => null])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('wilayahs', ['kode' => 'W09', 'nama' => 'Wilayah Baru']);
    }

    public function test_kode_wajib_unik(): void
    {
        Wilayah::create(['kode' => 'W01', 'nama' => 'Wilayah Satu']);

        $this->actingAs($this->admin())
            ->post('/wilayahs', ['kode' => 'W01', 'nama' => 'Duplikat'])
            ->assertSessionHasErrors('kode');
    }

    public function test_update_mengabaikan_kode_miliknya_sendiri(): void
    {
        $wilayah = Wilayah::create(['kode' => 'W01', 'nama' => 'Wilayah Satu']);

        $this->actingAs($this->admin())
            ->put("/wilayahs/{$wilayah->id}", ['kode' => 'W01', 'nama' => 'Nama Diubah'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('wilayahs', ['id' => $wilayah->id, 'nama' => 'Nama Diubah']);
    }

    public function test_wilayah_dengan_cabang_tidak_bisa_dihapus(): void
    {
        $wilayah = Wilayah::create(['kode' => 'W01', 'nama' => 'Wilayah Satu']);
        Cabang::create(['kode' => 'C01', 'nama' => 'Cabang Satu', 'wilayah_id' => $wilayah->id]);

        $this->actingAs($this->admin())
            ->delete("/wilayahs/{$wilayah->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('wilayahs', ['id' => $wilayah->id]);
    }

    public function test_wilayah_tanpa_cabang_bisa_dihapus(): void
    {
        $wilayah = Wilayah::create(['kode' => 'W01', 'nama' => 'Wilayah Satu']);

        $this->actingAs($this->admin())
            ->delete("/wilayahs/{$wilayah->id}")
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('wilayahs', ['id' => $wilayah->id]);
    }

    public function test_non_admin_ditolak(): void
    {
        Role::findOrCreate('kantor_cabang');
        $user = User::factory()->create()->assignRole('kantor_cabang');

        $this->actingAs($user)->get('/wilayahs')->assertForbidden();
    }
}
