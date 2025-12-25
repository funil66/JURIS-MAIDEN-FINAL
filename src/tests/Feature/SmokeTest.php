<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Route;

class SmokeTest extends TestCase
{
    public function test_essential_views_and_routes_exist(): void
    {
        $this->assertTrue(view()->exists('filament.pages.agenda-page'));

        // Important named routes should exist
        $routes = [
            'filament.funil.resources.signature-requests.index',
            'filament.funil.resources.events.index',
            'filament.funil.resources.diligences.index',
            'filament.funil.resources.processes.index',
        ];

        foreach ($routes as $name) {
            $this->assertTrue(Route::has($name), "Route $name should exist");
        }
    }
}
