<?php

namespace App\Console\Commands;

use App\Models\JurisSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class JurisExportCommand extends Command
{
    protected $signature = 'juris:export {--path= : Output path, defaults to storage/app/juris-settings-<ts>.json.enc}';

    protected $description = 'Export Juris settings to an encrypted file';

    public function handle(): int
    {
        $settings = JurisSetting::first();

        if (!$settings) {
            $this->error('No settings found');
            return self::FAILURE;
        }

        $payload = json_encode($settings->toArray());
        $encrypted = encrypt($payload);

        $path = $this->option('path') ?: 'juris-settings-' . now()->format('YmdHis') . '.json.enc';
        Storage::put($path, $encrypted);

        $this->info("Exported to: {$path}");
        return self::SUCCESS;
    }
}
