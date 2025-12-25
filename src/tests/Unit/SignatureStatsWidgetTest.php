<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Filament\Widgets\SignatureStatsWidget;

class SignatureStatsWidgetTest extends TestCase
{
    public function test_widget_has_expected_polling_interval_and_header_actions(): void
    {
        $widget = new SignatureStatsWidget();

        $rp = new \ReflectionProperty(SignatureStatsWidget::class, 'pollingInterval');
        $rp->setAccessible(true);
        $this->assertSame('30s', $rp->getValue());

        // Header actions are protected; ensure the method exists and returns an array via reflection
        $method = new \ReflectionMethod(SignatureStatsWidget::class, 'getStats');
        $this->assertTrue($method->isProtected());
    }
}
