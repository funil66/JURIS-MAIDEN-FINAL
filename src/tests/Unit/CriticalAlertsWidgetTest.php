<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Filament\Widgets\CriticalAlertsWidget;

class CriticalAlertsWidgetTest extends TestCase
{
    public function test_alert_helpers_return_expected_background_classes(): void
    {
        $widget = new CriticalAlertsWidget();

        $this->assertStringContainsString('bg-rose-500', $widget->alertBgCircleClass(['type' => 'danger']));
        $this->assertStringContainsString('bg-amber-500', $widget->alertBgCircleClass(['type' => 'warning']));
        $this->assertStringContainsString('bg-emerald-500', $widget->alertBgCircleClass(['type' => 'other']));
    }

    public function test_alert_card_class_defaults_and_specific(): void
    {
        $widget = new CriticalAlertsWidget();

        $this->assertStringContainsString('border-rose-200', $widget->alertCardClass(['type' => 'danger']));
        $this->assertStringContainsString('border-amber-200', $widget->alertCardClass(['type' => 'warning']));
        $this->assertStringContainsString('border-emerald-200', $widget->alertCardClass([]));
    }
}
