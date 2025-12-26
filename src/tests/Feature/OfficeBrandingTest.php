<?php

namespace Tests\Feature;

use App\Models\JurisSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficeBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        // Ensure settings exist
        JurisSetting::firstOrMakeFromConfig();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    }

    public function test_welcome_page_contains_office_footer()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee(config('juris.office_name'));
        $response->assertSee(config('juris.phone'));
    }

    public function test_admin_panel_topbar_shows_office_info()
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('admin');

        $this->actingAs($user)->get('/funil')
            ->assertStatus(200)
            ->assertSee(config('juris.office_name'))
            ->assertSee(config('juris.phone'));
    }
}
