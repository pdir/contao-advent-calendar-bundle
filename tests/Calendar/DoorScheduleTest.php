<?php

declare(strict_types=1);

/*
 * Advent calendar bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\AdventCalendarBundle\Tests\Calendar;

use Pdir\AdventCalendarBundle\Calendar\DoorSchedule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DoorScheduleTest extends TestCase
{
    private const CALENDAR = ['year' => 2026, 'openTimeWeekday' => '17:00', 'openTimeWeekend' => '15:30', 'openTimeLast' => '11:00', 'timeZone' => 'Europe/Berlin'];

    #[DataProvider('openingTimes')]
    public function testOpeningTime(int $day, string $expected): void
    {
        $opening = (new DoorSchedule())->getOpeningTime(self::CALENDAR, ['day' => $day, 'openAt' => '']);

        $this->assertSame($expected, $opening->format('Y-m-d H:i'));
    }

    public static function openingTimes(): iterable
    {
        yield 'Tuesday' => [1, '2026-12-01 17:00'];
        yield 'Saturday' => [5, '2026-12-05 15:30'];
        yield 'Sunday' => [6, '2026-12-06 15:30'];
        yield 'Monday' => [7, '2026-12-07 17:00'];
        yield 'Christmas Eve (Thursday)' => [24, '2026-12-24 11:00'];
    }

    public function testIndividualOpeningTimeWins(): void
    {
        $tz = new \DateTimeZone('Europe/Berlin');
        $openAt = (new \DateTimeImmutable('2026-12-03 09:00', $tz))->getTimestamp();
        $opening = (new DoorSchedule())->getOpeningTime(self::CALENDAR, ['day' => 3, 'openAt' => (string) $openAt]);

        $this->assertSame('2026-12-03 09:00', $opening->format('Y-m-d H:i'));
    }

    public function testDoorDateIgnoresIndividualOpeningTime(): void
    {
        $openAt = (new \DateTimeImmutable('2026-12-04 18:00', new \DateTimeZone('Europe/Berlin')))->getTimestamp();
        $date = (new DoorSchedule())->getDoorDate(self::CALENDAR, ['day' => 5, 'openAt' => (string) $openAt]);

        $this->assertSame('2026-12-05 00:00 Europe/Berlin', $date->format('Y-m-d H:i e'));
    }

    public function testIsOpen(): void
    {
        $schedule = new DoorSchedule();
        $tz = new \DateTimeZone('Europe/Berlin');

        $this->assertFalse($schedule->isOpen(self::CALENDAR, ['day' => 1], new \DateTimeImmutable('2026-12-01 16:59', $tz)));
        $this->assertTrue($schedule->isOpen(self::CALENDAR, ['day' => 1], new \DateTimeImmutable('2026-12-01 17:00', $tz)));
    }

    public function testUsesTheTimeZoneOfTheCalendar(): void
    {
        $previous = date_default_timezone_get();
        date_default_timezone_set('UTC');

        try {
            $opening = (new DoorSchedule())->getOpeningTime(self::CALENDAR, ['day' => 1, 'openAt' => '']);
        } finally {
            date_default_timezone_set($previous);
        }

        $this->assertSame('2026-12-01T17:00:00+01:00', $opening->format(\DATE_ATOM));
        $this->assertSame('2026-12-01T16:00:00+00:00', $opening->setTimezone(new \DateTimeZone('UTC'))->format(\DATE_ATOM));
    }

    public function testNextChange(): void
    {
        $schedule = new DoorSchedule();
        $tz = new \DateTimeZone('Europe/Berlin');
        $doors = array_map(static fn (int $day): array => ['day' => $day], range(1, 24));

        // Before the season: the "today" marker of door 1 starts at midnight
        $this->assertSame('2026-12-01 00:00', $schedule->getNextChange(self::CALENDAR, $doors, new \DateTimeImmutable('2026-11-30 12:00', $tz))?->format('Y-m-d H:i'));

        // During the day: the door opens
        $this->assertSame('2026-12-05 15:30', $schedule->getNextChange(self::CALENDAR, $doors, new \DateTimeImmutable('2026-12-05 10:00', $tz))?->format('Y-m-d H:i'));

        // In the evening: the next day starts
        $this->assertSame('2026-12-06 00:00', $schedule->getNextChange(self::CALENDAR, $doors, new \DateTimeImmutable('2026-12-05 18:00', $tz))?->format('Y-m-d H:i'));

        // Far before the season: the first door
        $this->assertSame('2026-12-01 17:00', $schedule->getNextChange(self::CALENDAR, $doors, new \DateTimeImmutable('2026-10-16 12:00', $tz))?->format('Y-m-d H:i'));

        // After the season: nothing changes any more
        $this->assertNull($schedule->getNextChange(self::CALENDAR, $doors, new \DateTimeImmutable('2026-12-27 12:00', $tz)));
    }
}
