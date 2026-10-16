<?php

declare(strict_types=1);

/*
 * Advent calendar bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\AdventCalendarBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Pdir\AdventCalendarBundle\Calendar\DoorGenerator;

/**
 * Creates the 24 doors when a calendar is saved for the first time.
 */
#[AsCallback(table: 'tl_advent_calendar', target: 'config.onsubmit')]
class CreateDoorsListener
{
    public function __construct(private readonly DoorGenerator $generator)
    {
    }

    public function __invoke(DataContainer $dc): void
    {
        if ($dc->id) {
            $this->generator->createMissingDoors((int) $dc->id);
        }
    }
}
