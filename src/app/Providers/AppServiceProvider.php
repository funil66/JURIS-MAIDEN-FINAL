<?php

namespace App\Providers;

use App\Models\GeneratedDocument;
use App\Models\GeneratedReport;
use App\Models\Service;
use App\Observers\GeneratedDocumentObserver;
use App\Observers\GeneratedReportObserver;
use App\Observers\ServiceObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registrar observers
        Service::observe(ServiceObserver::class);
        GeneratedDocument::observe(GeneratedDocumentObserver::class);
        GeneratedReport::observe(GeneratedReportObserver::class);
    }
}
