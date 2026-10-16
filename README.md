# Contao Advent Calendar Bundle

Adventskalender für Contao 5.7 und 6: ein Kalenderbild mit 24 Hotspots, Detailkarten
mit Sponsor und Gewinn, zeitgesteuerte Türchen und eine Gewinnerliste.

## Funktionen

- **Backend-Modul „Inhalte › Adventskalender“**: ein Kalender pro Jahr, beim ersten Speichern
  werden die 24 Türchen automatisch angelegt
- **Hotspot-Editor**: im Türchen ein Rechteck über das Fenster ziehen oder klicken, um es zu
  verschieben; die anderen Türchen werden zur Orientierung angezeigt. Positionen in Prozent,
  dadurch skaliert der Kalender mit
- **Öffnungszeiten** je Kalender (Mo–Fr, Sa/So, Heiligabend) und Zeitzone, abweichend pro Türchen
- **Serverseitige Freischaltung**: Inhalte geschlossener Türchen werden nicht ausgeliefert
  (kein Spicken im Quelltext). In der Vorschau mit „Unveröffentlichte Elemente anzeigen“
  sind alle Türchen offen
- **Seiten-Cache**: läuft automatisch ab, wenn das nächste Türchen öffnet (und um Mitternacht
  für die Markierung des aktuellen Türchens); Änderungen im Backend leeren den Cache per Tag
- **Barrierearm**: echte Buttons, natives `<dialog>` (Tastatur, Escape, Fokus), Beschriftung
  für Screenreader, `prefers-reduced-motion`; auf kleinen Bildschirmen zusätzlich eine
  Türchen-Liste, da Hotspots dort zu klein zum Tippen sind
- **Termine**: Ist das Contao-Kalender-Bundle installiert, lassen sich Veranstaltungskalender
  verknüpfen; die Termine eines Tages erscheinen verlinkt im geöffneten Türchen
  („Programm des Tages“, ohne wiederkehrende Termine)
- Geöffnete Türchen werden im Browser gemerkt (localStorage)
- Nutzt die Design-Tokens des Creuva-Themes (`--c-*`, `--radius-*`, `--space-*`), funktioniert
  aber auch ohne

## Inhaltselemente (Kategorie „Adventskalender“)

| Element | Inhalt |
|---|---|
| `advent_calendar` | Kalenderbild mit Hotspots, Detailkarten, Türchen-Liste |
| `advent_calendar_today` | Türchen des Tages; vor der Saison Countdown, danach nichts |
| `advent_calendar_winners` | Tabelle aller geöffneten Türchen mit Sponsor, Gewinn, Gewinnlos |

Alle Elemente können auf **„Automatisch (aktuelles Jahr)“** stehen. Dann zeigen sie den
Kalender des aktuellen Jahres (bzw. den letzten), und für die nächste Saison muss nur ein
neuer Kalender angelegt werden.

Im Element `advent_calendar` keine Bildgröße mit Zuschnitt verwenden, sonst stimmen die
Hotspots nicht mehr mit den Fenstern überein.

## Installation

```bash
composer require pdir/contao-advent-calendar-bundle
vendor/bin/contao-console contao:migrate
```

## Kalender per Konsole anlegen

```bash
vendor/bin/contao-console advent-calendar:create "Adventskalender 2026" \
    --year=2026 \
    --image=files/kalender/rathaus.jpg \
    --positions=positions.json \
    --jump-to=index \
    --event-calendar=Programm
```

`positions.json` enthält die Hotspots je Tag als `[links, oben, breite, höhe]` in Prozent
des Bildes: `{"1": [4.4, 60, 3, 6], "2": [11.4, 52, 3, 6], …}`. Ohne Datei werden die
Türchen auf einem Raster angeordnet.

## Templates

- `content_element/advent_calendar.html.twig`, `…_today`, `…_winners`
- `component/_advent_door_button.html.twig` (Hotspot bzw. Listen-Button)
- `component/_advent_door_card.html.twig` (Detailkarte)

Anpassungen wie gewohnt im Template Studio. CSS und JS liegen unter
`bundles/pdiradventcalendar/` und werden nur auf Seiten mit Kalender geladen.

## Tests

```bash
vendor/bin/phpunit
```
