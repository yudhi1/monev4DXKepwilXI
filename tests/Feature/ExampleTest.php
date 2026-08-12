<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_akar_mengarahkan_ke_dashboard(): void
    {
        $this->get('/')->assertRedirect('/dashboard');
    }

    public function test_dashboard_menolak_tamu(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
