<?php

namespace App\Console\Commands;

use App\Models\GoogleDriveSetting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GoogleTokensCommand extends Command
{
    protected $signature = 'google:tokens 
                            {--email= : User email to set tokens for}
                            {--access= : Access token string}
                            {--refresh= : Refresh token string}
                            {--expires_at= : Expiration datetime (Y-m-d H:i:s or timestamp) or seconds from now}
                            {--file= : Path to a JSON file containing tokens (access_token, refresh_token, expires_at or expires_in)}
                            {--force : Skip confirmation prompts}
                            ';

    protected $description = 'Set Google access/refresh tokens for a user (for recovery/manual setup)';

    public function handle(): int
    {
        $email = $this->option('email');
        $access = $this->option('access');
        $refresh = $this->option('refresh');
        $expires = $this->option('expires_at');
        $file = $this->option('file');
        $force = (bool) $this->option('force');

        if (!$email) {
            $this->error('Please provide --email');
            return Command::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User not found: {$email}");
            return Command::FAILURE;
        }

        $setting = GoogleDriveSetting::firstOrCreate(['user_id' => $user->id], [
            'auto_sync' => false,
            'is_connected' => false,
        ]);

        // If a file option is provided, read tokens from it
        if ($file) {
            if (!file_exists($file)) {
                $this->error("Token file not found: {$file}");
                return Command::FAILURE;
            }

            try {
                $contents = file_get_contents($file);
                $data = json_decode($contents, true);
            } catch (\Exception $e) {
                $this->error('Unable to read token file: ' . $e->getMessage());
                return Command::FAILURE;
            }

            if (!is_array($data)) {
                $this->error('Token file must contain valid JSON');
                return Command::FAILURE;
            }

            // map keys if present
            $access = $access ?? ($data['access_token'] ?? $data['access'] ?? null);
            $refresh = $refresh ?? ($data['refresh_token'] ?? $data['refresh'] ?? null);
            $expires = $expires ?? ($data['expires_at'] ?? $data['expires_in'] ?? null);
        }

        if (!$access) {
            $this->error('No access token provided. Use --access or --file to set tokens');
            return Command::FAILURE;
        }

        $expiresIn = $this->parseExpiresOption($expires);
        if ($expires !== null && $expiresIn === null) {
            $this->error('Invalid expires_at value');
            return Command::FAILURE;
        }

        // Confirmation before making changes
        if (!$force) {
            $confirm = $this->confirm("Apply tokens for user {$user->email}? This will overwrite existing tokens.");
            if (!$confirm) {
                $this->info('Aborted by user.');
                return Command::SUCCESS;
            }
        }

        try {
            $this->applyTokensToSetting($setting, $access, $refresh, $expiresIn);
            $this->info('Tokens updated successfully');
            Log::info('Google tokens updated via command', ['user_id' => $user->id]);
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to update tokens: ' . $e->getMessage());
            Log::error('Failed to update Google tokens', ['error' => $e->getMessage(), 'user_id' => $user->id]);
            return Command::FAILURE;
        }
    }

    protected function parseExpiresOption($expires): ?int
    {
        if ($expires === null) {
            return null;
        }

        // if numeric and small, treat as seconds; if numeric large, treat as timestamp
        if (is_numeric($expires)) {
            $int = (int) $expires;
            if ($int > 1000000000) {
                try {
                    $expiresAt = Carbon::createFromTimestamp($int);
                    return $expiresAt->diffInSeconds(now());
                } catch (\Exception $e) {
                    return null;
                }
            }

            return $int;
        }

        try {
            $dt = Carbon::parse($expires);
            return $dt->diffInSeconds(now());
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function applyTokensToSetting(GoogleDriveSetting $setting, string $access, ?string $refresh, ?int $expiresIn): void
    {
        $setting->updateTokens($access, $refresh, $expiresIn);
        $setting->update(['is_connected' => true]);
    }
}
