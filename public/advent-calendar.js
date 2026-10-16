/*
 * Advent calendar: opens the detail card of a door in a <dialog> and remembers
 * opened doors in the browser (localStorage). The content of closed doors is
 * not part of the page, it is rendered by the server once the door is open.
 */
(() => {
    if (window.adventCalendar) {
        return;
    }

    window.adventCalendar = true;

    const storageKey = (calendar) => `advent-calendar-${calendar.dataset.adventCalendar}`;

    const readVisited = (calendar) => {
        try {
            return JSON.parse(localStorage.getItem(storageKey(calendar))) || [];
        } catch (e) {
            return [];
        }
    };

    const markVisited = (calendar) => {
        const visited = readVisited(calendar);

        calendar.querySelectorAll('[data-advent-door]').forEach((door) => {
            door.classList.toggle('is-visited', visited.includes(Number(door.dataset.adventDoor)));
        });
    };

    const remember = (calendar, day) => {
        const visited = readVisited(calendar);

        if (!visited.includes(day)) {
            visited.push(day);

            try {
                localStorage.setItem(storageKey(calendar), JSON.stringify(visited));
            } catch (e) {
                // Storage not available (e.g. private mode)
            }
        }

        markVisited(calendar);
    };

    document.addEventListener('click', (event) => {
        const door = event.target.closest('[data-advent-door]');

        if (door) {
            const calendar = door.closest('[data-advent-calendar]');

            if (door.dataset.adventDialog) {
                document.getElementById(door.dataset.adventDialog)?.showModal();
                remember(calendar, Number(door.dataset.adventDoor));
            } else {
                const dialog = calendar.querySelector('[data-advent-locked]');

                dialog.querySelector('[data-advent-locked-text]').textContent = door.dataset.adventLockedText;
                dialog.showModal();
            }

            return;
        }

        // Close the dialog when clicking on the backdrop
        if (event.target instanceof HTMLDialogElement && event.target.closest('[data-advent-calendar]')) {
            event.target.close();
        }
    });

    const init = () => document.querySelectorAll('[data-advent-calendar]').forEach(markVisited);

    if ('loading' === document.readyState) {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
