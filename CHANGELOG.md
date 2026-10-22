# Changelog

Alle nennenswerten Änderungen an diesem Projekt werden in dieser Datei dokumentiert.

Das Format basiert auf [Keep a Changelog](https://keepachangelog.com/de/1.1.0/),
und dieses Projekt folgt [Semantic Versioning](https://semver.org/lang/de/).

## [1.0.0] - 2026-10-22

Erste Veröffentlichung für Contao 5.7 und 6 (PHP 8.3+).

### Hinzugefügt

- Backend-Modul „Inhalte › Adventskalender“ mit einem Kalender pro Jahr; beim ersten
  Speichern werden die 24 Türchen automatisch angelegt
- Hotspot-Editor im Türchen: Rechteck über das Fenster ziehen oder per Klick verschieben,
  die übrigen Türchen werden zur Orientierung angezeigt; Positionen in Prozent
- Öffnungszeiten je Kalender (Mo–Fr, Sa/So, Heiligabend) inkl. Zeitzone, pro Türchen
  überschreibbar
- Serverseitige Freischaltung: Inhalte geschlossener Türchen werden nicht ausgeliefert;
  in der Vorschau mit „Unveröffentlichte Elemente anzeigen“ sind alle Türchen offen
- Seiten-Cache, der beim Öffnen des nächsten Türchens und um Mitternacht abläuft;
  Änderungen im Backend invalidieren den Cache per Tag
- Inhaltselemente in der Kategorie „Adventskalender“:
  - `advent_calendar`: Kalenderbild mit Hotspots, Detailkarten und Türchen-Liste
  - `advent_calendar_today`: Türchen des Tages, vor der Saison mit Countdown
  - `advent_calendar_winners`: Gewinnerliste mit Sponsor, Gewinn und Gewinnlos
- Option „Automatisch (aktuelles Jahr)“ für alle Inhaltselemente
- Verknüpfung mit Veranstaltungskalendern (sofern das Contao-Kalender-Bundle installiert
  ist): Termine des Tages erscheinen als „Programm des Tages“ im geöffneten Türchen
- Barrierearme Umsetzung mit echten Buttons, nativem `<dialog>`, Screenreader-Beschriftungen,
  Unterstützung für `prefers-reduced-motion` und Türchen-Liste auf kleinen Bildschirmen
- Merken geöffneter Türchen im Browser (localStorage)
- Unterstützung der Design-Tokens des Creuva-Themes (`--c-*`, `--radius-*`, `--space-*`)
- Konsolenbefehl `advent-calendar:create` zum Anlegen eines Kalenders inkl. Hotspot-Positionen
  aus einer JSON-Datei
- Twig-Templates für Inhaltselemente und Komponenten, anpassbar im Template Studio
- Deutsche und englische Übersetzungen
- PHPUnit-Tests für die Öffnungslogik (`DoorSchedule`)

[1.0.0]: https://github.com/pdir/contao-advent-calendar-bundle/releases/tag/1.0.0
