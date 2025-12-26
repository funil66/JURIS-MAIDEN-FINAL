<?php

namespace Tests\Feature;

use App\Models\JurisSetting;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JurisExportImportTest extends TestCase
{
    public function test_export_and_import_commands()
    {
        Storage::fake();

        $s = JurisSetting::firstOrMakeFromConfig();
        $s->update(['office_name' => 'Unit Test Office']);

        // Run export command
        $this->artisan('juris:export', ['--path' => 'test/juris.json.enc'])->assertExitCode(0);

        Storage::assertExists('test/juris.json.enc');

        $encrypted = Storage::get('test/juris.json.enc');
        // Import back
        Storage::put('test/juris2.json.enc', $encrypted);

        $this->artisan('juris:import', ['path' => 'test/juris2.json.enc'])->assertExitCode(0);

        $this->assertDatabaseHas('juris_settings', ['office_name' => 'Unit Test Office']);
    }
}
