<?php

declare(strict_types=1);

/*
 * Advent calendar bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

use Pdir\AdventCalendarBundle\Widget\HotspotWidget;

$GLOBALS['BE_MOD']['content']['advent_calendar'] = [
    'tables' => ['tl_advent_calendar', 'tl_advent_calendar_door'],
];

$GLOBALS['BE_FFL']['adventHotspot'] = HotspotWidget::class;
