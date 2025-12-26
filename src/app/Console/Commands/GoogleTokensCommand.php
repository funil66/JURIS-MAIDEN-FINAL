<?php

namespace App\Console\Commands;

use App\Models\GoogleDriveSetting;
use App\Models\User;
use Illuminate\Console\Command;
use Carbon\Carbon;

class GoogleTokensCommand extends Command
{
    protected $signature = 'google:tokens 
                            {--email= : User email to set tokens for}
                            {--access= : Access token string}
                            {--refresh= : Refresh token string}
                            {--expires_at= : Expiration datetime (Y-m-d H:i:s or timestamp) or seconds from now}
                            ';

    protected $description = 'Set Google access/refresh tokens for a user (for recovery/manual setup)';

    public function handle(): int
    {
        $email = $this->option('email');
        $access = $this->option('access');
        $refresh = $this->option('refresh');
        $expires = $this->option('expires_at');

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

        $expiresIn = null;
        if ($expires) {
            // if numeric and small, treat as seconds; if numeric large, treat as timestamp
            if (is_numeric($expires)) {
                $int = (int) $expires;
                if ($int > 1000000000) {
                    $expiresAt = Carbon::createFromTimestamp($int);
                    $expiresIn = $expiresAt->diffInSeconds(now());
                } else {
                    $expiresIn = $int;
                }
            } else {
                try {
                    $dt = Carbon::parse($expires);
                    $expiresIn = $dt->diffInSeconds(now());
                } catch (\Exception $e) {
                    $this->error('Invalid expires_at value');
                    return Command::FAILURE;
                }
            }
        }

        if ($access) {
            $setting->updateTokens($access, $refresh, $expiresIn);
            $setting->update(['is_connected' => true]);
            $this->info('Tokens updated successfully');
        } else {
            $this->error('No access token provided. Use --access to set a token');
            return Command::FAILURE;
        }

        $this->info('Done.');
        return Command::SUCCESS;
    }
}
