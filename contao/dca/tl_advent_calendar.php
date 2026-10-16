<?php

declare(strict_types=1);

/*
 * Advent calendar bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

use Contao\CalendarBundle\ContaoCalendarBundle;
use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_advent_calendar'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ctable' => ['tl_advent_calendar_door'],
        'switchToEdit' => true,
        'enableVersioning' => true,
        'markAsCopy' => 'title',
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'tstamp' => 'index',
            ],
        ],
    ],

    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTED,
            'fields' => ['year'],
            'flag' => DataContainer::SORT_DESC,
            'panelLayout' => 'search,limit',
            'defaultSearchField' => 'title',
        ],
        'label' => [
            'fields' => ['title', 'year'],
            'format' => '%s <span class="label-info">[%s]</span>',
        ],
    ],

    'palettes' => [
        'default' => '{title_legend},title,year;{image_legend},singleSRC,logoSize;{schedule_legend},openTimeWeekday,openTimeWeekend,openTimeLast,timeZone;{redirect_legend},jumpTo',
    ],

    'fields' => [
        'id' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'autoincrement' => true],
        ],
        'tstamp' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
        ],
        'title' => [
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
        'year' => [
            'inputType' => 'text',
            'default' => (int) date('Y'),
            'eval' => ['mandatory' => true, 'rgxp' => 'natural', 'minval' => 2000, 'maxval' => 2100, 'maxlength' => 4, 'tl_class' => 'w50'],
            'sql' => ['type' => 'smallint', 'unsigned' => true, 'default' => 0],
        ],
        'singleSRC' => [
            'inputType' => 'fileTree',
            'eval' => ['filesOnly' => true, 'fieldType' => 'radio', 'extensions' => '%contao.image.valid_extensions%', 'tl_class' => 'clr'],
            'sql' => 'binary(16) NULL',
        ],
        'logoSize' => [
            'label' => &$GLOBALS['TL_LANG']['MSC']['imgSize'],
            'inputType' => 'imageSize',
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => ['rgxp' => 'natural', 'includeBlankOption' => true, 'nospace' => true, 'helpwizard' => true, 'tl_class' => 'w50 clr'],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => '', 'platformOptions' => ['collation' => 'ascii_bin']],
        ],
        'openTimeWeekday' => [
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'rgxp' => 'custom', 'customRgxp' => '/^([01]\d|2[0-3]):[0-5]\d$/', 'maxlength' => 5, 'placeholder' => 'HH:MM', 'tl_class' => 'w33'],
            'sql' => ['type' => 'string', 'length' => 5, 'default' => '17:00'],
        ],
        'openTimeWeekend' => [
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'rgxp' => 'custom', 'customRgxp' => '/^([01]\d|2[0-3]):[0-5]\d$/', 'maxlength' => 5, 'placeholder' => 'HH:MM', 'tl_class' => 'w33'],
            'sql' => ['type' => 'string', 'length' => 5, 'default' => '15:30'],
        ],
        'openTimeLast' => [
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'rgxp' => 'custom', 'customRgxp' => '/^([01]\d|2[0-3]):[0-5]\d$/', 'maxlength' => 5, 'placeholder' => 'HH:MM', 'tl_class' => 'w33'],
            'sql' => ['type' => 'string', 'length' => 5, 'default' => '11:00'],
        ],
        'timeZone' => [
            'inputType' => 'select',
            'options_callback' => static fn (): array => \DateTimeZone::listIdentifiers(),
            'eval' => ['chosen' => true, 'tl_class' => 'w50 clr'],
            'sql' => ['type' => 'string', 'length' => 64, 'default' => 'Europe/Berlin'],
        ],
        'eventCalendars' => [
            'inputType' => 'checkbox',
            'eval' => ['multiple' => true, 'tl_class' => 'clr'],
            'sql' => ['type' => 'blob', 'notnull' => false],
        ],
        'jumpTo' => [
            'inputType' => 'pageTree',
            'foreignKey' => 'tl_page.title',
            'eval' => ['fieldType' => 'radio', 'tl_class' => 'clr'],
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
            'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
        ],
    ],
];

// Link the events of each day (requires the calendar bundle)
if (class_exists(ContaoCalendarBundle::class)) {
    $GLOBALS['TL_DCA']['tl_advent_calendar']['palettes']['default'] = str_replace('{redirect_legend}', '{events_legend},eventCalendars;{redirect_legend}', $GLOBALS['TL_DCA']['tl_advent_calendar']['palettes']['default']);
}
