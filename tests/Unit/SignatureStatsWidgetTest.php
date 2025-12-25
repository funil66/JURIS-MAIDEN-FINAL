<?php

use PHPUnit\Framework\TestCase;
use App\Filament\Widgets\SignatureStatsWidget;

class SignatureStatsWidgetTest extends TestCase
{
    public function test_get_stats_returns_four_stats(): void
    {
        $widget = new SignatureStatsWidget();
        $stats = $widget->getStats();

        $this->assertIsArray($stats);
        $this->assertCount(4, $stats);

        foreach ($stats as $stat) {
            $this->assertIsObject($stat);
        }
    }
}
