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
 * The calendar image with one hotspot per door and a detail card per open door.
 */
#[AsContentElement(type: 'advent_calendar', category: 'advent', template: 'content_element/advent_calendar')]
class AdventCalendarController extends AbstractAdventCalendarController
{
    protected function addCalendarToTemplate(FragmentTemplate $template, array $calendar, array $doors, \DateTimeImmutable $now, ContentModel $model): bool
    {
        $template->set('image', $this->studio
            ->createFigureBuilder()
            ->fromUuid($calendar['singleSRC'] ?: '')
            ->setSize($model->size)
            ->buildIfResourceExists(),
        );

        $template->set('doors', $doors);

        return true;
    }
}
