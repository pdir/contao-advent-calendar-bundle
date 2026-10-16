<?php

declare(strict_types=1);

/*
 * Advent calendar bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\AdventCalendarBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;

/**
 * Teaser for the door of the current day ("Today behind door 12: …"); before
 * the season it shows a countdown to the first door, afterwards nothing.
 */
#[AsContentElement(type: 'advent_calendar_today', category: 'advent', template: 'content_element/advent_calendar_today')]
class AdventCalendarTodayController extends AbstractAdventCalendarController
{
    protected function addCalendarToTemplate(FragmentTemplate $template, array $calendar, array $doors, \DateTimeImmutable $now, ContentModel $model): bool
    {
        $today = null;
        $first = null;

        foreach ($doors as $door) {
            if ($door['today']) {
                $today = $door;
            }

            if (null === $first || $door['opening'] < $first['opening']) {
                $first = $door;
            }
        }

        if (null !== $first && $first['opening'] > $now && null === $today) {
            $template->set('door', null);
            $template->set('days_left', (int) $now->setTime(0, 0)->diff($first['opening']->setTime(0, 0))->days);
            $template->set('first_door', $first);

            return true;
        }

        if (null === $today) {
            return false;
        }

        $template->set('door', $today);
        $template->set('days_left', 0);
        $template->set('first_door', $first);

        return true;
    }
}
