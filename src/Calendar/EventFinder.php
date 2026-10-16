<?php

declare(strict_types=1);

/*
 * Advent calendar bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\AdventCalendarBundle\Calendar;

use Contao\CalendarEventsModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\StringUtil;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

/**
 * Finds the events of a day in the event calendars linked to an advent calendar
 * (requires the Contao calendar bundle; recurring events are not considered).
 */
class EventFinder
{
    private bool|null $available = null;

    public function __construct(
        private readonly Connection $connection,
        private readonly ContaoFramework $framework,
        private readonly ContentUrlGenerator $urlGenerator,
    ) {
    }

    public function isAvailable(): bool
    {
        return $this->available ??= class_exists(CalendarEventsModel::class) && $this->connection->createSchemaManager()->tablesExist(['tl_calendar_events']);
    }

    /**
     * @param array<string, mixed> $calendar row of tl_advent_calendar
     *
     * @return list<int>
     */
    public function getEventCalendarIds(array $calendar): array
    {
        return array_values(array_filter(array_map('intval', StringUtil::deserialize($calendar['eventCalendars'] ?? '', true))));
    }

    /**
     * @param array<string, mixed> $calendar row of tl_advent_calendar
     * @param \DateTimeImmutable   $day      the date of the door (only the date is used)
     *
     * @return list<array{id: int, title: string, time: string, location: string, url: string|null}>
     */
    public function findByDay(array $calendar, \DateTimeImmutable $day, \DateTimeImmutable $now): array
    {
        $calendarIds = $this->getEventCalendarIds($calendar);

        if (!$calendarIds || !$this->isAvailable()) {
            return [];
        }

        // Contao stores and renders events in the time zone of the server, so the
        // day is interpreted in the same way as in the event list and reader
        $timeZone = new \DateTimeZone(date_default_timezone_get());
        $day = new \DateTimeImmutable($day->format('Y-m-d'), $timeZone);
        $dayStart = $day->getTimestamp();
        $dayEnd = $day->setTime(23, 59, 59)->getTimestamp();

        $rows = $this->connection->fetchAllAssociative(
            "SELECT id, title, addTime, startTime, endTime, location
                FROM tl_calendar_events
                WHERE pid IN (?) AND published = 1
                    AND (start = '' OR start <= ?) AND (stop = '' OR stop > ?)
                    AND startDate <= ? AND COALESCE(endDate, startDate) >= ?
                ORDER BY startTime, id",
            [$calendarIds, $now->getTimestamp(), $now->getTimestamp(), $dayEnd, $dayStart],
            [ArrayParameterType::INTEGER],
        );

        $this->framework->initialize();
        $events = [];

        foreach ($rows as $row) {
            $time = '';

            if ($row['addTime']) {
                $start = (new \DateTimeImmutable('@'.$row['startTime']))->setTimezone($timeZone);
                $end = (new \DateTimeImmutable('@'.$row['endTime']))->setTimezone($timeZone);
                $time = $start->format('H:i').($end > $start ? '–'.$end->format('H:i') : '');
            }

            $events[] = [
                'id' => (int) $row['id'],
                'title' => (string) $row['title'],
                'time' => $time,
                'location' => (string) $row['location'],
                'url' => $this->generateUrl((int) $row['id']),
            ];
        }

        return $events;
    }

    private function generateUrl(int $id): string|null
    {
        $event = $this->framework->getAdapter(CalendarEventsModel::class)->findById($id);

        if (null === $event) {
            return null;
        }

        try {
            return $this->urlGenerator->generate($event);
        } catch (\Throwable) {
            return null;
        }
    }
}
