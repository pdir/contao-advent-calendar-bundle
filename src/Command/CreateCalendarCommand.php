<?php

declare(strict_types=1);

/*
 * Advent calendar bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

namespace Pdir\AdventCalendarBundle\Command;

use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Doctrine\DBAL\Connection;
use Pdir\AdventCalendarBundle\Calendar\DoorGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Creates a calendar with 24 doors, e.g. after importing a demo website:
 *
 *   advent-calendar:create "Adventskalender 2026" --year=2026 --image=files/kalender/rathaus.jpg --positions=positions.json --event-calendar=Programm
 *
 * The positions file contains the hotspots per day in percent of the image:
 * {"1": [12.5, 40, 3, 6], "2": […], …}
 */
#[AsCommand(name: 'advent-calendar:create', description: 'Creates an advent calendar with 24 doors.')]
class CreateCalendarCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
        private readonly DoorGenerator $generator,
        #[Autowire(service: 'contao.filesystem.virtual.files')]
        private readonly VirtualFilesystemInterface $filesStorage,
        #[Autowire(param: 'contao.upload_path')]
        private readonly string $uploadPath,
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('title', InputArgument::REQUIRED, 'Title of the calendar')
            ->addOption('year', null, InputOption::VALUE_REQUIRED, 'Year of the calendar', date('Y'))
            ->addOption('image', null, InputOption::VALUE_REQUIRED, 'Calendar image, path inside the upload folder (e.g. files/kalender/rathaus.jpg)')
            ->addOption('positions', null, InputOption::VALUE_REQUIRED, 'JSON file with the hotspots per day ({"1": [x, y, width, height], …})')
            ->addOption('timezone', null, InputOption::VALUE_REQUIRED, 'Time zone of the opening times', 'Europe/Berlin')
            ->addOption('jump-to', null, InputOption::VALUE_REQUIRED, 'Alias or ID of the calendar page')
            ->addOption('event-calendar', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Title or ID of an event calendar whose events are linked in the doors')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $this->assertSchemaIsUpToDate();
            $uuid = $this->resolveImage((string) $input->getOption('image'));
            $positions = $this->loadPositions((string) $input->getOption('positions'));
            $jumpTo = $this->resolvePage((string) $input->getOption('jump-to'));
            $eventCalendars = array_map($this->resolveEventCalendar(...), $input->getOption('event-calendar'));

            if (!\in_array($input->getOption('timezone'), \DateTimeZone::listIdentifiers(), true)) {
                throw new \InvalidArgumentException(\sprintf('Unknown time zone "%s".', $input->getOption('timezone')));
            }
        } catch (\InvalidArgumentException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $this->connection->insert('tl_advent_calendar', [
            'tstamp' => time(),
            'title' => (string) $input->getArgument('title'),
            'year' => (int) $input->getOption('year'),
            'singleSRC' => $uuid,
            'jumpTo' => $jumpTo,
            'openTimeWeekday' => '17:00',
            'openTimeWeekend' => '15:30',
            'openTimeLast' => '11:00',
            'timeZone' => (string) $input->getOption('timezone'),
            'eventCalendars' => $eventCalendars ? serialize($eventCalendars) : null,
        ]);

        $id = (int) $this->connection->lastInsertId();
        $doors = $this->generator->createMissingDoors($id, $positions);

        $io->success(\sprintf('Created calendar ID %d with %d doors. Edit it in the back end under "Content › Advent calendar".', $id, $doors));

        return Command::SUCCESS;
    }

    /**
     * The command writes the tables directly, so all columns must exist.
     */
    private function assertSchemaIsUpToDate(): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $required = [
            'tl_advent_calendar' => ['title', 'year', 'singleSRC', 'jumpTo', 'openTimeWeekday', 'openTimeWeekend', 'openTimeLast', 'timeZone', 'eventCalendars'],
            'tl_advent_calendar_door' => ['pid', 'day', 'hotspot', 'published'],
        ];

        foreach ($required as $table => $columns) {
            $existing = $schemaManager->tablesExist([$table]) ? array_map(static fn ($column): string => strtolower($column->getName()), $schemaManager->listTableColumns($table)) : [];
            $missing = array_filter($columns, static fn (string $column): bool => !\in_array(strtolower($column), $existing, true));

            if ($missing) {
                throw new \InvalidArgumentException(\sprintf('The database is not up to date (missing in %s: %s). Please run "contao:migrate" first.', $table, implode(', ', $missing)));
            }
        }
    }

    private function resolveImage(string $path): string|null
    {
        if ('' === $path) {
            return null;
        }

        $path = preg_replace('#^'.preg_quote($this->uploadPath, '#').'/#', '', ltrim($path, '/'));
        $uuid = $this->filesStorage->get($path)?->getUuid();

        if (null === $uuid) {
            throw new \InvalidArgumentException(\sprintf('The image "%s/%s" does not exist in the file manager.', $this->uploadPath, $path));
        }

        return $uuid->toBinary();
    }

    /**
     * @return array<int, array{0: float, 1: float, 2: float, 3: float}>
     */
    private function loadPositions(string $file): array
    {
        if ('' === $file) {
            return [];
        }

        if (!is_file($file) && is_file($this->projectDir.'/'.$file)) {
            $file = $this->projectDir.'/'.$file;
        }

        $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;

        if (!\is_array($data)) {
            throw new \InvalidArgumentException(\sprintf('The positions file "%s" does not exist or is not valid JSON.', $file));
        }

        $positions = [];

        foreach ($data as $day => $hotspot) {
            if ((int) $day < 1 || (int) $day > 24 || !\is_array($hotspot) || 4 !== \count($hotspot)) {
                throw new \InvalidArgumentException(\sprintf('Invalid position for day "%s" (expected [x, y, width, height]).', $day));
            }

            $positions[(int) $day] = array_map('floatval', array_values($hotspot));
        }

        return $positions;
    }

    private function resolveEventCalendar(string $calendar): string
    {
        if (!$this->connection->createSchemaManager()->tablesExist(['tl_calendar'])) {
            throw new \InvalidArgumentException('The calendar bundle is not installed.');
        }

        $id = $this->connection->fetchOne('SELECT id FROM tl_calendar WHERE id = ? OR title = ? ORDER BY id LIMIT 1', [(int) $calendar, $calendar]);

        if (false === $id) {
            throw new \InvalidArgumentException(\sprintf('The event calendar "%s" does not exist.', $calendar));
        }

        return (string) $id;
    }

    private function resolvePage(string $page): int
    {
        if ('' === $page) {
            return 0;
        }

        $id = $this->connection->fetchOne('SELECT id FROM tl_page WHERE id = ? OR alias = ? ORDER BY id LIMIT 1', [(int) $page, $page]);

        if (false === $id) {
            throw new \InvalidArgumentException(\sprintf('The page "%s" does not exist.', $page));
        }

        return (int) $id;
    }
}
