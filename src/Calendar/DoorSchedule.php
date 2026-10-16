<?php

declare(strict_types=1);

/*
 * Advent calendar bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\AdventCalendarBundle\Calendar;

/**
 * Calculates when a door opens.
 *
 * Without an individual opening time (field "openAt"), a door opens on
 * December <day> of the calendar year at the time configured in the calendar:
 * Monday to Friday "openTimeWeekday", Saturday and Sunday "openTimeWeekend"
 * and on December 24 "openTimeLast", in the time zone of the calendar.
 */
class DoorSchedule
{
    /**
     * @param array<string, mixed> $calendar row of tl_advent_calendar
     * @param array<string, mixed> $door     row of tl_advent_calendar_door
     */
    public function getOpeningTime(array $calendar, array $door): \DateTimeImmutable
    {
        $timeZone = $this->getTimeZone($calendar);

        if ('' !== (string) ($door['openAt'] ?? '')) {
            return (new \DateTimeImmutable('@'.(int) $door['openAt']))->setTimezone($timeZone);
        }

        $day = (int) $door['day'];
        $date = (new \DateTimeImmutable('now', $timeZone))->setDate((int) $calendar['year'], 12, $day)->setTime(0, 0);

        $time = match (true) {
            24 === $day => $calendar['openTimeLast'] ?? '',
            (int) $date->format('N') >= 6 => $calendar['openTimeWeekend'] ?? '',
            default => $calendar['openTimeWeekday'] ?? '',
        };

        if (!preg_match('/^(\d{1,2}):(\d{2})$/', (string) $time, $matches)) {
            return $date;
        }

        return $date->setTime((int) $matches[1], (int) $matches[2]);
    }

    /**
     * Returns the date of the door (December <day> of the calendar year), which may
     * differ from the opening time if the door has an individual opening time.
     *
     * @param array<string, mixed> $calendar
     * @param array<string, mixed> $door
     */
    public function getDoorDate(array $calendar, array $door): \DateTimeImmutable
    {
        return (new \DateTimeImmutable('now', $this->getTimeZone($calendar)))->setDate((int) $calendar['year'], 12, (int) $door['day'])->setTime(0, 0);
    }

    /**
     * @param array<string, mixed> $calendar
     * @param array<string, mixed> $door
     */
    public function isOpen(array $calendar, array $door, \DateTimeInterface $now): bool
    {
        return $this->getOpeningTime($calendar, $door) <= $now;
    }

    /**
     * @param array<string, mixed> $calendar
     */
    public function getTimeZone(array $calendar): \DateTimeZone
    {
        try {
            return new \DateTimeZone((string) ($calendar['timeZone'] ?? '') ?: date_default_timezone_get());
        } catch (\Exception) {
            return new \DateTimeZone(date_default_timezone_get());
        }
    }

    /**
     * Returns the next point in time at which the output of the calendar changes
     * (a door opens or the "today" marker moves on), so the page cache can expire.
     *
     * @param array<string, mixed>       $calendar
     * @param list<array<string, mixed>> $doors
     */
    public function getNextChange(array $calendar, array $doors, \DateTimeImmutable $now): \DateTimeImmutable|null
    {
        $now = $now->setTimezone($this->getTimeZone($calendar));
        $candidates = [];

        foreach ($doors as $door) {
            $opening = $this->getOpeningTime($calendar, $door);

            if ($opening > $now) {
                $candidates[] = $opening;
            }
        }

        // The door of the current day is highlighted from midnight to midnight
        if ((int) $now->format('Y') === (int) $calendar['year'] && \in_array($now->format('m-d'), ['11-30', ...array_map(static fn (int $d): string => \sprintf('12-%02d', $d), range(1, 24))], true)) {
            $candidates[] = $now->modify('tomorrow');
        }

        return $candidates ? min($candidates) : null;
    }
}
