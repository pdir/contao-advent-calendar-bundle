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
use Contao\StringUtil;
use Pdir\AdventCalendarBundle\Calendar\CalendarRepository;
use Pdir\AdventCalendarBundle\Calendar\DoorSchedule;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Shows day, sponsor, prize, opening time and missing data in the door list.
 */
#[AsCallback(table: 'tl_advent_calendar_door', target: 'list.label.label')]
class DoorLabelListener
{
    /**
     * @var array<int, array<string, mixed>|null>
     */
    private array $calendars = [];

    public function __construct(
        private readonly CalendarRepository $repository,
        private readonly DoorSchedule $schedule,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public function __invoke(array $row, string $label, DataContainer $dc): string
    {
        $pid = (int) $row['pid'];
        $calendar = $this->calendars[$pid] ??= $this->repository->findCalendar($pid, 0);
        $trans = fn (string $key): string => $this->translator->trans('tl_advent_calendar_door.'.$key, [], 'contao_tl_advent_calendar_door');

        $parts = [\sprintf('<strong>%s</strong>', $this->translator->trans('MSC.adventDoor', [$row['day']], 'contao_default'))];
        $parts[] = $row['sponsor'] ? StringUtil::specialchars((string) $row['sponsor']) : \sprintf('<span class="tl_gray">%s</span>', $trans('labelNoSponsor'));

        if ($row['prize']) {
            $parts[] = StringUtil::specialchars((string) $row['prize']);
        }

        $info = [];

        if (null !== $calendar) {
            $info[] = $this->schedule->getOpeningTime($calendar, $row)->format('d.m. H:i');
        }

        if (4 !== \count(StringUtil::deserialize($row['hotspot'], true))) {
            $info[] = $trans('labelNoHotspot');
        }

        if ($row['winner']) {
            $info[] = $trans('labelWinner').' '.StringUtil::specialchars((string) $row['winner']);
        }

        return implode(' · ', $parts).($info ? ' <span class="label-info">['.implode(', ', $info).']</span>' : '');
    }
}
