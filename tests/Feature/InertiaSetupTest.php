<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InertiaSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_inertia_menolak_tamu(): void
    {
        $this->get('/_inertia-check')->assertRedirect('/login');
    }

    public function test_halaman_inertia_render_untuk_user_login(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/_inertia-check');

        $response->assertOk();
        $response->assertSee('data-page', false);
        $response->assertSee('InertiaCheck', false);
    }
}
