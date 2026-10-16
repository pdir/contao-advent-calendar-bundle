<?php

declare(strict_types=1);

/*
 * Advent calendar bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\AdventCalendarBundle\Widget;

use Contao\StringUtil;
use Contao\System;
use Contao\Widget;

/**
 * Hotspot editor: shows the calendar image with all doors and lets the editor
 * draw the area of the current door (drag) or move it (click). The position is
 * stored as [x, y, width, height] in percent of the image.
 */
class HotspotWidget extends Widget
{
    protected $blnSubmitInput = true;

    protected $strTemplate = 'be_widget';

    private const KEYS = ['x', 'y', 'w', 'h'];

    protected function validator($varInput): mixed
    {
        $values = array_map(static fn ($value): string => str_replace(',', '.', trim((string) $value)), array_values((array) $varInput));

        if ('' === implode('', $values)) {
            return '';
        }

        $lang = $GLOBALS['TL_LANG']['tl_advent_calendar_door'] ?? [];

        if (4 !== \count($values) || \in_array(false, array_map('is_numeric', $values), true)) {
            $this->addError($lang['hotspotInvalid'] ?? 'Invalid hotspot.');

            return $values;
        }

        [$x, $y, $w, $h] = array_map('floatval', $values);

        if ($x < 0 || $y < 0 || $w <= 0 || $h <= 0 || $x + $w > 100.01 || $y + $h > 100.01) {
            $this->addError($lang['hotspotRange'] ?? 'The hotspot must lie within the image.');
        }

        return array_map(static fn (float $value): string => (string) round($value, 2), [$x, $y, $w, $h]);
    }

    public function generate(): string
    {
        $GLOBALS['TL_CSS']['advent_hotspot'] = 'bundles/pdiradventcalendar/backend.css|static';
        $GLOBALS['TL_JAVASCRIPT']['advent_hotspot'] = 'bundles/pdiradventcalendar/backend.js';

        $lang = $GLOBALS['TL_LANG']['tl_advent_calendar_door'] ?? [];
        $value = \is_array($this->varValue) ? array_values($this->varValue) : StringUtil::deserialize($this->varValue, true);
        $record = $this->dataContainer?->getCurrentRecord() ?? [];
        [$imageUrl, $others] = $this->loadCalendar((int) ($record['pid'] ?? 0), (int) ($record['id'] ?? 0));

        $stage = '';

        if ($imageUrl) {
            $markers = '';

            foreach ($others as $door) {
                $markers .= \sprintf('<span class="advent-hotspot__other" style="%s">%d</span>', $this->getStyle($door['hotspot']), $door['day']);
            }

            $stage = \sprintf(
                '<div class="advent-hotspot__stage" data-advent-hotspot-stage><img src="%s" alt="" draggable="false">%s<span class="advent-hotspot__current" data-advent-hotspot-rect style="%s"%s>%d</span></div>',
                StringUtil::specialcharsUrl($imageUrl),
                $markers,
                $this->getStyle($value),
                4 === \count($value) ? '' : ' hidden',
                (int) ($record['day'] ?? 0),
            );
        } else {
            $stage = \sprintf('<p class="tl_info">%s</p>', $lang['hotspotNoImage'] ?? 'Please select an image in the calendar first.');
        }

        $fields = '';

        foreach (self::KEYS as $i => $key) {
            $fields .= \sprintf(
                '<label>%s <input type="number" name="%s[%d]" id="ctrl_%s_%s" class="tl_text" step="0.01" min="0" max="100" value="%s" data-advent-hotspot-input="%s"></label>',
                $lang['hotspot_'.$key] ?? strtoupper($key),
                $this->strName,
                $i,
                $this->strId,
                $key,
                self::specialcharsValue($value[$i] ?? ''),
                $key,
            );
        }

        return \sprintf(
            '<div class="advent-hotspot%s" data-advent-hotspot>%s<div class="advent-hotspot__fields">%s<button type="button" class="tl_submit" data-advent-hotspot-reset>%s</button></div></div>',
            $this->strClass ? ' '.$this->strClass : '',
            $stage,
            $fields,
            $lang['hotspotReset'] ?? 'Remove',
        );
    }

    /**
     * @return array{0: string|null, 1: list<array{day: int, hotspot: array<mixed>}>}
     */
    private function loadCalendar(int $calendarId, int $doorId): array
    {
        if (!$calendarId) {
            return [null, []];
        }

        $container = System::getContainer();
        $connection = $container->get('database_connection');
        $uuid = $connection->fetchOne('SELECT singleSRC FROM tl_advent_calendar WHERE id = ?', [$calendarId]);
        $imageUrl = null;

        if ($uuid) {
            // Use a resized copy, so the image is shown even if the folder is not public
            $figure = $container->get('contao.image.studio')
                ->createFigureBuilder()
                ->fromUuid($uuid)
                ->setSize([1600, 0, 'proportional'])
                ->buildIfResourceExists()
            ;

            $imageUrl = $figure?->getImage()->getImageSrc();
        }

        $others = [];

        foreach ($connection->fetchAllAssociative('SELECT day, hotspot FROM tl_advent_calendar_door WHERE pid = ? AND id != ? ORDER BY day', [$calendarId, $doorId]) as $row) {
            $hotspot = StringUtil::deserialize($row['hotspot'], true);

            if (4 === \count($hotspot)) {
                $others[] = ['day' => (int) $row['day'], 'hotspot' => $hotspot];
            }
        }

        return [$imageUrl, $others];
    }

    /**
     * @param array<mixed> $hotspot
     */
    private function getStyle(array $hotspot): string
    {
        if (4 !== \count($hotspot)) {
            return '';
        }

        [$x, $y, $w, $h] = array_map('floatval', array_values($hotspot));

        return \sprintf('left:%s%%;top:%s%%;width:%s%%;height:%s%%', $x, $y, $w, $h);
    }
}
