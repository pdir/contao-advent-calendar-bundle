<?php

declare(strict_types=1);

/*
 * Advent calendar bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\AdventCalendarBundle\Calendar;

use Doctrine\DBAL\Connection;

class CalendarRepository
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * Returns the calendar with the given ID or, for ID 0, the calendar of the
     * given year (or the most recent one before it, or the next one after it).
     *
     * @return array<string, mixed>|null
     */
    public function findCalendar(int $id, int $currentYear): array|null
    {
        if ($id > 0) {
            return $this->connection->fetchAssociative('SELECT * FROM tl_advent_calendar WHERE id = ?', [$id]) ?: null;
        }

        return $this->connection->fetchAssociative('SELECT * FROM tl_advent_calendar WHERE year <= ? ORDER BY year DESC, id DESC LIMIT 1', [$currentYear])
            ?: $this->connection->fetchAssociative('SELECT * FROM tl_advent_calendar ORDER BY year ASC, id ASC LIMIT 1')
            ?: null;
    }

    /**
     * @return array<int, string> ID => label
     */
    public function getCalendarOptions(): array
    {
        $options = [];

        foreach ($this->connection->fetchAllAssociative('SELECT id, title, year FROM tl_advent_calendar ORDER BY year DESC, title') as $row) {
            $options[(int) $row['id']] = \sprintf('%s (%s)', $row['title'], $row['year']);
        }

        return $options;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findDoors(int $calendarId, bool $publishedOnly = true): array
    {
        $query = 'SELECT * FROM tl_advent_calendar_door WHERE pid = ?'.($publishedOnly ? ' AND published = 1' : '').' ORDER BY day, id';

        return $this->connection->fetchAllAssociative($query, [$calendarId]);
    }

    /**
     * @return list<int>
     */
    public function findDays(int $calendarId, int $excludeDoorId = 0): array
    {
        return array_map('intval', $this->connection->fetchFirstColumn('SELECT day FROM tl_advent_calendar_door WHERE pid = ? AND id != ?', [$calendarId, $excludeDoorId]));
    }
}
