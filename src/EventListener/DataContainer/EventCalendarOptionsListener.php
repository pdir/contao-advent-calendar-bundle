<?php

declare(strict_types=1);

/*
 * Advent calendar bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\AdventCalendarBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Doctrine\DBAL\Connection;
use Pdir\AdventCalendarBundle\Calendar\EventFinder;

#[AsCallback(table: 'tl_advent_calendar', target: 'fields.eventCalendars.options')]
class EventCalendarOptionsListener
{
    public function __construct(
        private readonly Connection $connection,
        private readonly EventFinder $eventFinder,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function __invoke(): array
    {
        if (!$this->eventFinder->isAvailable()) {
            return [];
        }

        return array_map('strval', $this->connection->fetchAllKeyValue('SELECT id, title FROM tl_calendar ORDER BY title'));
    }
}
