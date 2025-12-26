<?php

namespace Tests\Feature;

use App\Models\GoogleDriveSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GoogleTokensTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        // ensure admin role exists
        Role::firstOrCreate(['name' => 'admin']);
    }

    public function test_can_import_tokens_via_artisan_command()
    {
        $user = User::factory()->create(['email' => 'import-test@local', 'name' => 'Import Test']);
        $user->assignRole('admin');

        $access = 'PHPUNIT_ACCESS_' . Str::random(8);
        $refresh = 'PHPUNIT_REFRESH_' . Str::random(8);

        $this->artisan('google:tokens', [
            '--email' => $user->email,
            '--access' => $access,
            '--refresh' => $refresh,
            '--expires_at' => 3600,
        ])->assertExitCode(0);

        $setting = GoogleDriveSetting::where('user_id', $user->id)->first();
        $this->assertNotNull($setting);
        $this->assertTrue($setting->is_connected);
        $this->assertNotNull($setting->token_expires_at);
        $this->assertEquals($access, $setting->getDecryptedAccessToken());
        $this->assertEquals($refresh, $setting->getDecryptedRefreshToken());

        // call disconnect and verify cleared
        $setting->disconnect();
        $setting->refresh();
        $this->assertFalse($setting->is_connected);
        $this->assertNull($setting->access_token);
        $this->assertNull($setting->refresh_token);
    }
}
