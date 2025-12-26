<?php

namespace App\Console\Commands;

use App\Models\JurisSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class JurisImportCommand extends Command
{
    protected $signature = 'juris:import {path : Path to encrypted file or - for stdin}';

    protected $description = 'Import Juris settings from an encrypted file or stdin';

    public function handle(): int
    {
        $path = $this->argument('path');

        if ($path === '-') {
            $content = trim(stream_get_contents(STDIN));
        } else {
            if (!Storage::exists($path)) {
                $this->error("File does not exist: {$path}");
                return self::FAILURE;
            }
            $content = Storage::get($path);
        }

        try {
            $json = decrypt($content);
            $data = json_decode($json, true);
        } catch (\Exception $e) {
            $this->error('Failed to decrypt or parse file: ' . $e->getMessage());
            return self::FAILURE;
        }

        if (!$data) {
            $this->error('No data found in file');
            return self::FAILURE;
        }

        $settings = JurisSetting::firstOrCreate([]);
        $settings->update($data);

        $this->info('Settings imported successfully');
        return self::SUCCESS;
    }
}
