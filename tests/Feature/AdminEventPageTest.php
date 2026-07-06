<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEventPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_event_page_renders_for_authorized_user(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('username', 'superadmin')->firstOrFail();

        $response = $this->actingAs($admin)->get('/admin/acara');

        $response->assertOk();
        $response->assertSee('Atur acara utama dengan tampilan lebar');
        $response->assertSee('Snapshot acara');
        $response->assertSee('data-admin-open', false);
    }
}
