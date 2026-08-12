<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CabangInertiaTest extends TestCase
{
    use RefreshDatabase;

    private Wilayah $wilayah;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wilayah = Wilayah::create(['kode' => 'W01', 'nama' => 'Wilayah Satu']);
    }

    private function admin(): User
    {
        Role::findOrCreate('admin');

        return User::factory()->create()->assignRole('admin');
    }

    public function test_admin_bisa_melihat_daftar_cabang(): void
    {
        Cabang::create(['kode' => 'C01', 'nama' => 'Cabang Satu', 'wilayah_id' => $this->wilayah->id]);

        $this->actingAs($this->admin())
            ->get('/cabangs')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Cabang/Index')
                ->has('cabangs.data', 1)
                ->where('cabangs.data.0.wilayah.nama', 'Wilayah Satu')
            );
    }

    public function test_filter_wilayah_menyaring_hasil(): void
    {
        $lain = Wilayah::create(['kode' => 'W02', 'nama' => 'Wilayah Dua']);
        Cabang::create(['kode' => 'C01', 'nama' => 'Cabang Satu', 'wilayah_id' => $this->wilayah->id]);
        Cabang::create(['kode' => 'C02', 'nama' => 'Cabang Dua', 'wilayah_id' => $lain->id]);

        $this->actingAs($this->admin())
            ->get("/cabangs?wilayah_id={$lain->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('cabangs.data', 1)
                ->where('cabangs.data.0.nama', 'Cabang Dua')
            );
    }

    public function test_admin_bisa_menyimpan_cabang(): void
    {
        $this->actingAs($this->admin())
            ->post('/cabangs', ['kode' => 'C09', 'nama' => 'Cabang Baru', 'wilayah_id' => $this->wilayah->id])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('cabangs', ['kode' => 'C09', 'wilayah_id' => $this->wilayah->id]);
    }

    public function test_wilayah_wajib_ada(): void
    {
        $this->actingAs($this->admin())
            ->post('/cabangs', ['kode' => 'C09', 'nama' => 'Cabang Baru', 'wilayah_id' => 99999])
            ->assertSessionHasErrors('wilayah_id');
    }

    public function test_cabang_dengan_user_tidak_bisa_dihapus(): void
    {
        $cabang = Cabang::create(['kode' => 'C01', 'nama' => 'Cabang Satu', 'wilayah_id' => $this->wilayah->id]);
        User::factory()->create(['cabang_id' => $cabang->id]);

        $this->actingAs($this->admin())
            ->delete("/cabangs/{$cabang->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('cabangs', ['id' => $cabang->id]);
    }

    public function test_non_admin_ditolak(): void
    {
        Role::findOrCreate('kantor_cabang');
        $user = User::factory()->create()->assignRole('kantor_cabang');

        $this->actingAs($user)->get('/cabangs')->assertForbidden();
    }
}
