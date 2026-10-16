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
 * Table of all open doors with sponsor, prize and winning ticket.
 */
#[AsContentElement(type: 'advent_calendar_winners', category: 'advent', template: 'content_element/advent_calendar_winners')]
class AdventCalendarWinnersController extends AbstractAdventCalendarController
{
    protected function addCalendarToTemplate(FragmentTemplate $template, array $calendar, array $doors, \DateTimeImmutable $now, ContentModel $model): bool
    {
        $open = array_values(array_filter($doors, static fn (array $door): bool => $door['open']));

        $template->set('doors', $open);
        $template->set('has_winners', [] !== array_filter(array_column($open, 'winner')));

        return true;
    }
}
