<?php

declare(strict_types=1);

/*
 * Advent calendar bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\AdventCalendarBundle\Calendar;

use Doctrine\DBAL\Connection;

/**
 * Creates the 24 doors of a new calendar. Without explicit positions, the
 * hotspots are placed on a 6×4 grid in a shuffled order, ready to be moved
 * onto the windows of the calendar image in the hotspot editor.
 */
class DoorGenerator
{
    private const SHUFFLED_DAYS = [13, 7, 21, 2, 18, 10, 24, 5, 16, 1, 11, 22, 8, 19, 3, 14, 9, 23, 4, 15, 20, 6, 12, 17];

    public function __construct(
        private readonly Connection $connection,
        private readonly CalendarRepository $repository,
    ) {
    }

    /**
     * @param array<int, array{0: float|int|string, 1: float|int|string, 2: float|int|string, 3: float|int|string}> $positions day => [x, y, width, height] in percent
     *
     * @return int number of created doors
     */
    public function createMissingDoors(int $calendarId, array $positions = []): int
    {
        $existing = $this->repository->findDays($calendarId);
        $positions += $this->getDefaultPositions();
        $created = 0;

        for ($day = 1; $day <= 24; ++$day) {
            if (\in_array($day, $existing, true)) {
                continue;
            }

            $this->connection->insert('tl_advent_calendar_door', [
                'pid' => $calendarId,
                'tstamp' => time(),
                'day' => $day,
                'hotspot' => serialize(array_map(static fn ($v): string => (string) round((float) $v, 2), array_values($positions[$day]))),
                'published' => 1,
            ]);

            ++$created;
        }

        return $created;
    }

    /**
     * @return array<int, array{0: float, 1: float, 2: float, 3: float}>
     */
    public function getDefaultPositions(): array
    {
        $positions = [];

        foreach (self::SHUFFLED_DAYS as $i => $day) {
            $positions[$day] = [20 + ($i % 6) * 10.5, 20 + intdiv($i, 6) * 16, 6.0, 10.0];
        }

        return $positions;
    }
}
