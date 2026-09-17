<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LoginInertiaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
    }

    private function buatUser(array $ubah = []): User
    {
        return User::factory()->create(array_merge([
            'name' => 'Budi',
            'password' => Hash::make('rahasia123'),
            'is_active' => true,
        ], $ubah))->assignRole('admin');
    }

    public function test_halaman_login_render_sebagai_inertia(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Auth/Login'));
    }

    public function test_kredensial_benar_masuk_ke_dashboard(): void
    {
        $user = $this->buatUser();

        $this->post('/login', ['name' => 'Budi', 'password' => 'rahasia123'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_kredensial_salah_ditolak(): void
    {
        $this->buatUser();

        $this->from('/login')
            ->post('/login', ['name' => 'Budi', 'password' => 'salah'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('name');

        $this->assertGuest();
    }

    public function test_akun_nonaktif_ditolak(): void
    {
        $this->buatUser(['is_active' => false]);

        $this->from('/login')
            ->post('/login', ['name' => 'Budi', 'password' => 'rahasia123'])
            ->assertSessionHasErrors('name');

        $this->assertGuest();
    }

    public function test_pesan_kesalahan_sampai_ke_halaman(): void
    {
        $this->buatUser();

        $this->from('/login')->post('/login', ['name' => 'Budi', 'password' => 'salah']);

        // Pesan harus tersedia sebagai prop errors agar bisa ditampilkan di form.
        $this->get('/login')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('errors.name', 'Nama atau password salah.')
            );
    }

    public function test_pegawai_masuk_dengan_npp(): void
    {
        $pegawai = User::factory()->pegawai()->create([
            'npp' => '123456',
            'password' => Hash::make('rahasia123'),
            'is_active' => true,
        ]);

        $this->post('/login', ['mode' => 'pegawai', 'npp' => '123456', 'password' => 'rahasia123'])
            ->assertRedirect('/apps');

        $this->assertAuthenticatedAs($pegawai);
    }

    public function test_npp_salah_ditolak_dengan_pesan_pada_field_npp(): void
    {
        User::factory()->pegawai()->create([
            'npp' => '123456',
            'password' => Hash::make('rahasia123'),
        ]);

        $this->from('/login')
            ->post('/login', ['mode' => 'pegawai', 'npp' => '123456', 'password' => 'salah'])
            ->assertSessionHasErrors('npp');

        $this->assertGuest();
    }

    /** Nama akun pegawai tidak boleh menjadi jalan masuk lewat tab unit kerja. */
    public function test_pegawai_tidak_bisa_masuk_lewat_nama(): void
    {
        User::factory()->pegawai()->create([
            'name' => 'Dewi',
            'npp' => '123456',
            'password' => Hash::make('rahasia123'),
        ]);

        $this->from('/login')
            ->post('/login', ['name' => 'Dewi', 'password' => 'rahasia123'])
            ->assertSessionHasErrors('name');

        $this->assertGuest();
    }

    public function test_user_login_diarahkan_dari_halaman_login(): void
    {
        $this->actingAs($this->buatUser())
            ->get('/login')
            ->assertRedirect();
    }

    public function test_logout_mengembalikan_ke_halaman_login(): void
    {
        $this->actingAs($this->buatUser())
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }
}
