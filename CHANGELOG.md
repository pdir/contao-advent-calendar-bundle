# Changelog

[//]: <> (
Types of changes
    Added for new features.
    Changed for changes in existing functionality.
    Deprecated for soon-to-be removed features.
    Removed for now removed features.
    Fixed for any bug fixes.
    Security in case of vulnerabilities.
)

## [1.0.0](https://github.com/pdir/contao-advent-calendar-bundle/tree/1.0.0) – 2026-10-22

- [Added] Support Contao 5.7 and 6 (PHP 8.3+)
- [Added] Backend module "Content › Advent calendar" with one calendar per year; the 24 doors are created automatically on first save
- [Added] Hotspot editor in the door: drag a rectangle over the window or click to move it, the other doors are shown for orientation; positions in percent, so the calendar scales
- [Added] Opening times per calendar (Mon–Fri, Sat/Sun, Christmas Eve) incl. time zone, overridable per door
- [Added] Server-side unlocking: contents of closed doors are not delivered; in the preview with "Show unpublished elements" all doors are open
- [Added] Page cache that expires when the next door opens and at midnight; changes in the backend invalidate the cache by tag
- [Added] Content elements in the category "Advent calendar": `advent_calendar` (calendar image with hotspots, detail cards and door list), `advent_calendar_today` (door of the day, countdown before the season), `advent_calendar_winners` (list of winners with sponsor, prize and winning ticket)
- [Added] Option "Automatic (current year)" for all content elements
- [Added] Link event calendars (if the Contao calendar bundle is installed): the events of the day appear as "Programme of the day" in the opened door
- [Added] Accessible implementation with real buttons, native `<dialog>`, screen reader labels, support for `prefers-reduced-motion` and a door list on small screens
- [Added] Opened doors are remembered in the browser (localStorage)
- [Added] Support for the design tokens of the Creuva theme (`--c-*`, `--radius-*`, `--space-*`)
- [Added] Console command `advent-calendar:create` to create a calendar incl. hotspot positions from a JSON file
- [Added] Twig templates for content elements and components, customizable in the Template Studio
- [Added] German and English translations
- [Added] PHPUnit tests for the opening logic (`DoorSchedule`)
