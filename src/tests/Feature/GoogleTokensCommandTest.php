<?php

namespace Tests\Feature;

use App\Models\GoogleDriveSetting;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GoogleTokensCommandTest extends TestCase
{
    public function test_sets_tokens_with_options()
    {
        $user = User::factory()->create(['email' => 'tokens@example.test']);

        $this->artisan('google:tokens', [
            '--email' => $user->email,
            '--access' => 'access-abc',
            '--refresh' => 'refresh-xyz',
            '--expires_at' => 3600,
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseHas('google_drive_settings', [
            'user_id' => $user->id,
            'is_connected' => true,
        ]);

        $setting = GoogleDriveSetting::where('user_id', $user->id)->first();
        $this->assertNotNull($setting->token_expires_at);
    }

    public function test_sets_tokens_from_file()
    {
        $user = User::factory()->create(['email' => 'file@example.test']);

        $tmp = tempnam(sys_get_temp_dir(), 'tokens');
        file_put_contents($tmp, json_encode([
            'access_token' => 'file-access',
            'refresh_token' => 'file-refresh',
            'expires_in' => 7200,
        ]));

        $this->artisan('google:tokens', [
            '--email' => $user->email,
            '--file' => $tmp,
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseHas('google_drive_settings', [
            'user_id' => $user->id,
            'is_connected' => true,
        ]);

        @unlink($tmp);
    }

    public function test_fails_when_file_missing()
    {
        $user = User::factory()->create(['email' => 'nomatch@example.test']);

        $this->artisan('google:tokens', [
            '--email' => $user->email,
            '--file' => '/path/does/not/exist.json',
            '--force' => true,
        ])->assertExitCode(1);
    }
}
