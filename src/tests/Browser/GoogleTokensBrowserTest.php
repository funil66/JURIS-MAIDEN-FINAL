<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Spatie\Permission\Models\Role;

class GoogleTokensBrowserTest extends DuskTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin']);
    }

    public function test_admin_can_import_and_clear_tokens_via_filament_ui()
    {
        $user = User::factory()->create(['email' => 'dusk-admin@local', 'name' => 'Dusk Admin']);
        $user->assignRole('admin');

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/funil/admin-google-drive')
                // wait for table and click the import button for the first row
                ->waitForText('Google Drive - Admin')
                ->pause(500)
                ->with('.filament-tables-row', function (Browser $row) {
                    $row->press('Importar Tokens');
                })
                ->waitForText('Importar Tokens')
                ->type('access', 'DUSK_ACCESS_123')
                ->type('refresh', 'DUSK_REFRESH_456')
                ->type('expires_at', '3600')
                ->press('Salvar')
                ->waitForText('Tokens importados')
                ->pause(500)
                // now click 'Limpar Tokens' on the same row
                ->with('.filament-tables-row', function (Browser $row) {
                    $row->press('Limpar Tokens');
                })
                ->waitForText('Tokens limpos');
        });
    }
}
