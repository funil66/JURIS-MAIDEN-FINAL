<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Filament\Pages\AgendaPage;
use Illuminate\Support\Carbon;

class AgendaPageTest extends TestCase
{
    public function test_calendar_generation_and_navigation(): void
    {
        // Use a partial mock to avoid hitting the database
        $page = \Mockery::mock(AgendaPage::class)->makePartial();
        $sampleEvents = collect([
            [
                'id' => 'event-1',
                'type' => 'event',
                'title' => 'Teste Evento',
                'start' => Carbon::now()->format('Y-m-d'),
                'end' => Carbon::now()->format('Y-m-d'),
                'color' => 'indigo',
                'description' => 'Desc',
                'all_day' => false,
                'url' => null,
            ],
        ]);

        $page->shouldReceive('getEvents')->andReturn($sampleEvents);

        $this->assertIsArray($page->getCalendarDays());

        $current = $page->currentDate;
        $page->previousMonth();
        $this->assertNotSame($current, $page->currentDate);

        $page->nextMonth();
        $this->assertSame(
            Carbon::parse($current)->format('Y-m'),
            Carbon::parse($page->currentDate)->format('Y-m')
        );

        $days = $page->getCalendarDays();
        $this->assertGreaterThanOrEqual(28, count($days));

        // select a date and ensure getEventsForDate returns a collection
        $page->selectDate($page->currentDate);
        $events = $page->getEventsForDate($page->currentDate);
        $this->assertIsIterable($events);
    }

    public function test_upcoming_events_return_collection(): void
    {
        $page = \Mockery::mock(AgendaPage::class)->makePartial();
        $sampleEvents = collect([
            [
                'id' => 'event-1',
                'type' => 'event',
                'title' => 'Proximo Evento',
                'start' => Carbon::now()->addDay()->format('Y-m-d'),
                'end' => Carbon::now()->addDay()->format('Y-m-d'),
                'color' => 'indigo',
                'description' => 'Desc',
                'all_day' => false,
                'url' => null,
            ],
        ]);

        $page->shouldReceive('getEvents')->andReturn($sampleEvents);

        $upcoming = $page->getUpcomingEvents();

        $this->assertIsIterable($upcoming);
        $this->assertCount(1, $upcoming);
    }
}
