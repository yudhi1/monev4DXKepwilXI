<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserInertiaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'kedeputian_wilayah', 'kantor_cabang'] as $role) {
            Role::findOrCreate($role);
        }
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }

    public function test_admin_bisa_melihat_daftar_user(): void
    {
        $this->actingAs($this->admin())
            ->get('/users')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('User/Index')
                ->has('users.data', 1)
                ->where('users.data.0.role', 'admin')
            );
    }

    public function test_user_aktif_diurutkan_lebih_dulu(): void
    {
        // Dibuat paling akhir, tapi nonaktif — harus turun di bawah admin.
        User::factory()->create(['name' => 'User Nonaktif', 'is_active' => false])->assignRole('kantor_cabang');

        $this->actingAs($this->admin())
            ->get('/users')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('users.data', 2)
                ->where('users.data.0.is_active', true)
                ->where('users.data.1.name', 'User Nonaktif')
            );
    }

    public function test_filter_role_menyaring_hasil(): void
    {
        User::factory()->create(['name' => 'Petugas Cabang'])->assignRole('kantor_cabang');

        $this->actingAs($this->admin())
            ->get('/users?role=kantor_cabang')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.name', 'Petugas Cabang')
            );
    }

    public function test_menyimpan_user_membuat_email_otomatis_dan_role(): void
    {
        $this->actingAs($this->admin())
            ->post('/users', [
                'name' => 'Budi Santoso',
                'password' => 'rahasia123',
                'role' => 'kantor_cabang',
                'is_active' => true,
            ])
            ->assertSessionHas('success');

        $user = User::where('name', 'Budi Santoso')->first();

        $this->assertNotNull($user);
        $this->assertSame('budi.santoso@monev.local', $user->email);
        $this->assertTrue($user->hasRole('kantor_cabang'));
    }

    public function test_password_wajib_saat_membuat_user(): void
    {
        $this->actingAs($this->admin())
            ->post('/users', ['name' => 'Tanpa Password', 'role' => 'kantor_cabang'])
            ->assertSessionHasErrors('password');
    }

    public function test_password_kosong_saat_edit_tidak_mengubah_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('passwordlama')]);
        $user->assignRole('kantor_cabang');
        $hashLama = $user->password;

        $this->actingAs($this->admin())
            ->put("/users/{$user->id}", [
                'name' => 'Nama Diubah',
                'password' => '',
                'role' => 'kantor_cabang',
                'is_active' => true,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame($hashLama, $user->fresh()->password);
        $this->assertSame('Nama Diubah', $user->fresh()->name);
    }

    public function test_mengubah_role_mengganti_role_lama(): void
    {
        $user = User::factory()->create();
        $user->assignRole('kantor_cabang');

        $this->actingAs($this->admin())
            ->put("/users/{$user->id}", [
                'name' => $user->name,
                'role' => 'kedeputian_wilayah',
                'is_active' => true,
            ])
            ->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertTrue($user->hasRole('kedeputian_wilayah'));
        $this->assertFalse($user->hasRole('kantor_cabang'));
    }

    public function test_admin_tidak_bisa_menghapus_akun_sendiri(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->delete("/users/{$admin->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_non_admin_ditolak(): void
    {
        $user = User::factory()->create()->assignRole('kantor_cabang');

        $this->actingAs($user)->get('/users')->assertForbidden();
    }
}
