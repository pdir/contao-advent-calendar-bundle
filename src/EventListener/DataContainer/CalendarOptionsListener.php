<?php

declare(strict_types=1);

/*
 * Advent calendar bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\AdventCalendarBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Pdir\AdventCalendarBundle\Calendar\CalendarRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCallback(table: 'tl_content', target: 'fields.adventCalendar.options')]
class CalendarOptionsListener
{
    public function __construct(
        private readonly CalendarRepository $repository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function __invoke(): array
    {
        return [0 => $this->translator->trans('tl_content.adventCalendarAuto', [], 'contao_tl_content')] + $this->repository->getCalendarOptions();
    }
}
