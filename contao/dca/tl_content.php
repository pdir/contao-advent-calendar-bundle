<?php

declare(strict_types=1);

/*
 * Advent calendar bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

$GLOBALS['TL_DCA']['tl_content']['palettes']['advent_calendar'] = '{type_legend},type,headline;{advent_calendar_legend},adventCalendar,size;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';
$GLOBALS['TL_DCA']['tl_content']['palettes']['advent_calendar_today'] = '{type_legend},type,headline;{advent_calendar_legend},adventCalendar;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';
$GLOBALS['TL_DCA']['tl_content']['palettes']['advent_calendar_winners'] = $GLOBALS['TL_DCA']['tl_content']['palettes']['advent_calendar_today'];

// 0 = calendar of the current year (or the most recent one), so the element
// does not have to be changed for the next season
$GLOBALS['TL_DCA']['tl_content']['fields']['adventCalendar'] = [
    'inputType' => 'select',
    'eval' => ['chosen' => true, 'tl_class' => 'w50'],
    'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
];
