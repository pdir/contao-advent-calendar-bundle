<?php

declare(strict_types=1);

/*
 * Advent calendar bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\AdventCalendarBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\Validator;
use Pdir\AdventCalendarBundle\Calendar\CalendarRepository;
use Pdir\AdventCalendarBundle\Calendar\DoorSchedule;
use Pdir\AdventCalendarBundle\Calendar\EventFinder;
use Psr\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Loads the calendar and its doors, makes sure the content of a door is only
 * sent to the browser once the door is open (or in the preview mode) and lets
 * the page cache expire when the next door opens.
 */
abstract class AbstractAdventCalendarController extends AbstractContentElementController
{
    public function __construct(
        protected readonly CalendarRepository $repository,
        protected readonly DoorSchedule $schedule,
        protected readonly Studio $studio,
        protected readonly InsertTagParser $insertTagParser,
        protected readonly TokenChecker $tokenChecker,
        protected readonly TranslatorInterface $translator,
        protected readonly ContentUrlGenerator $urlGenerator,
        protected readonly ClockInterface $clock,
        protected readonly EventFinder $eventFinder,
    ) {
    }

    /**
     * Adds the element specific data to the template. Return false to render nothing.
     *
     * @param array<string, mixed>       $calendar
     * @param list<array<string, mixed>> $doors    see prepareDoors()
     */
    abstract protected function addCalendarToTemplate(FragmentTemplate $template, array $calendar, array $doors, \DateTimeImmutable $now, ContentModel $model): bool;

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $now = \DateTimeImmutable::createFromInterface($this->clock->now());
        $calendar = $this->repository->findCalendar((int) $model->adventCalendar, (int) $now->format('Y'));

        // Render the element again as soon as a calendar is created or changed
        $this->tagResponse('contao.db.tl_advent_calendar');

        if (null === $calendar) {
            return new Response();
        }

        $this->tagResponse('contao.db.tl_advent_calendar.'.$calendar['id']);

        // Linked events: changes in the event calendars invalidate the element, too
        foreach ($this->eventFinder->getEventCalendarIds($calendar) as $eventCalendarId) {
            $this->tagResponse('contao.db.tl_calendar.'.$eventCalendarId);
        }

        // All dates (opening times, "today") refer to the time zone of the calendar
        $now = $now->setTimezone($this->schedule->getTimeZone($calendar));

        $rows = $this->repository->findDoors((int) $calendar['id']);

        $template->set('calendar', [
            'id' => (int) $calendar['id'],
            'title' => $calendar['title'],
            'year' => (int) $calendar['year'],
            'url' => $this->getCalendarUrl($calendar),
        ]);

        $template->set('element_id', (int) $model->id);
        $template->set('preview', $this->isPreview());

        if (!$this->addCalendarToTemplate($template, $calendar, $this->prepareDoors($calendar, $rows, $now, $request), $now, $model)) {
            return new Response();
        }

        $response = $template->getResponse();

        $this->addSharedMaxAgeToResponse($response, $model);

        if (!$this->isPreview() && $next = $this->schedule->getNextChange($calendar, $rows, $now)) {
            $maxAge = max(1, $next->getTimestamp() - $now->getTimestamp());
            $current = $response->headers->getCacheControlDirective('s-maxage');
            $response->setSharedMaxAge(null !== $current ? min((int) $current, $maxAge) : $maxAge);
        }

        return $response;
    }

    /**
     * @param array<string, mixed>       $calendar
     * @param list<array<string, mixed>> $rows
     *
     * @return list<array<string, mixed>>
     */
    protected function prepareDoors(array $calendar, array $rows, \DateTimeImmutable $now, Request $request): array
    {
        $preview = $this->isPreview();
        $logoSize = StringUtil::deserialize($calendar['logoSize'] ?? '', true);
        $doors = [];

        $formatter = new \IntlDateFormatter(
            $request->getLocale() ?: 'de',
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            $now->getTimezone(),
            null,
            $this->translator->trans('MSC.adventDateFormat', [], 'contao_default'),
        );

        foreach ($rows as $row) {
            $opening = $this->schedule->getOpeningTime($calendar, $row);
            $isOpen = $opening <= $now;
            $hotspot = array_map('floatval', StringUtil::deserialize($row['hotspot'], true));
            $openingLabel = (string) $formatter->format($opening);

            $door = [
                'id' => (int) $row['id'],
                'day' => (int) $row['day'],
                'open' => $isOpen || $preview,
                'today' => $opening->format('Y-m-d') === $now->format('Y-m-d'),
                'opening' => $opening,
                'opening_label' => $openingLabel,
                'locked_text' => $this->translator->trans('MSC.adventLocked', [$row['day'], $openingLabel], 'contao_default'),
                'hotspot' => 4 === \count($hotspot) && $hotspot[2] > 0 && $hotspot[3] > 0 ? array_combine(['x', 'y', 'w', 'h'], $hotspot) : null,
            ];

            // Never send the content of a closed door to the browser
            if ($door['open']) {
                $door += [
                    'sponsor' => (string) $row['sponsor'],
                    'sponsor_url' => $this->resolveUrl((string) $row['sponsorUrl'], $request),
                    'logo' => $this->studio
                        ->createFigureBuilder()
                        ->fromUuid($row['sponsorLogo'] ?: '')
                        ->setSize(array_filter($logoSize) ? $logoSize : null)
                        ->buildIfResourceExists(),
                    'prize' => (string) $row['prize'],
                    'description' => (string) ($row['description'] ?? ''),
                    'winner' => (string) $row['winner'],
                    'events' => $this->eventFinder->findByDay($calendar, $this->schedule->getDoorDate($calendar, $row), $now),
                ];
            }

            $doors[] = $door;
        }

        return $doors;
    }

    /**
     * @param array<string, mixed> $calendar
     */
    protected function getCalendarUrl(array $calendar): string|null
    {
        if (!$calendar['jumpTo'] || !$page = PageModel::findPublishedById((int) $calendar['jumpTo'])) {
            return null;
        }

        try {
            return $this->urlGenerator->generate($page);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function isPreview(): bool
    {
        return $this->tokenChecker->isPreviewMode();
    }

    private function resolveUrl(string $url, Request $request): string
    {
        if ('' === $url) {
            return '';
        }

        $href = $this->insertTagParser->replaceInline($url);

        if (Validator::isRelativeUrl($href)) {
            $href = $request->getBasePath().'/'.$href;
        }

        return $href;
    }
}
