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
use Pdir\AdventCalendarBundle\Calendar\CalendarRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Every day may only exist once per calendar.
 */
#[AsCallback(table: 'tl_advent_calendar_door', target: 'fields.day.save')]
class UniqueDayListener
{
    public function __construct(
        private readonly CalendarRepository $repository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function __invoke(mixed $value, DataContainer $dc): mixed
    {
        $pid = (int) ($dc->getCurrentRecord()['pid'] ?? 0);

        if ($pid && \in_array((int) $value, $this->repository->findDays($pid, (int) $dc->id), true)) {
            throw new \RuntimeException($this->translator->trans('ERR.adventDayExists', [$value], 'contao_default'));
        }

        return $value;
    }
}
