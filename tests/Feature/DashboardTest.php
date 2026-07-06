<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_with_related_resident_and_transaction_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('username', 'superadmin')->firstOrFail();

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSee('Dashboard');
        $response->assertSee('Pendaftaran Warga Terbaru');
        $response->assertSee('Komposisi Kontribusi Warga');
        $response->assertSee('Transaksi Terbaru');
    }
}
