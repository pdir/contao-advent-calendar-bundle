/*
 * Hotspot editor of the advent calendar doors (see HotspotWidget).
 * Uses event delegation, so it also works with Turbo page visits.
 */
(() => {
    if (window.adventHotspotEditor) {
        return;
    }

    window.adventHotspotEditor = true;

    const keys = ['x', 'y', 'w', 'h'];
    const round = (value) => Math.round(value * 100) / 100;
    const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

    const getInputs = (widget) => Object.fromEntries(keys.map((key) => [key, widget.querySelector(`[data-advent-hotspot-input="${key}"]`)]));

    const render = (widget) => {
        const rect = widget.querySelector('[data-advent-hotspot-rect]');
        const inputs = getInputs(widget);

        if (!rect) {
            return;
        }

        const values = keys.map((key) => inputs[key].value);

        if (values.some((value) => '' === value)) {
            rect.hidden = true;

            return;
        }

        rect.hidden = false;
        rect.style.left = `${values[0]}%`;
        rect.style.top = `${values[1]}%`;
        rect.style.width = `${values[2]}%`;
        rect.style.height = `${values[3]}%`;
    };

    const write = (widget, area) => {
        const inputs = getInputs(widget);

        keys.forEach((key) => {
            inputs[key].value = round(area[key]);
        });

        render(widget);
    };

    const getPoint = (stage, event) => {
        const box = stage.getBoundingClientRect();

        return {
            x: clamp(((event.clientX - box.left) / box.width) * 100, 0, 100),
            y: clamp(((event.clientY - box.top) / box.height) * 100, 0, 100),
        };
    };

    let drag = null;

    document.addEventListener('pointerdown', (event) => {
        const stage = event.target.closest('[data-advent-hotspot-stage]');

        if (!stage || 0 !== event.button) {
            return;
        }

        event.preventDefault();
        stage.setPointerCapture(event.pointerId);
        drag = { stage, widget: stage.closest('[data-advent-hotspot]'), start: getPoint(stage, event), moved: false };
    });

    document.addEventListener('pointermove', (event) => {
        if (!drag) {
            return;
        }

        const point = getPoint(drag.stage, event);

        if (Math.abs(point.x - drag.start.x) < 0.5 && Math.abs(point.y - drag.start.y) < 0.5) {
            return;
        }

        drag.moved = true;

        write(drag.widget, {
            x: Math.min(point.x, drag.start.x),
            y: Math.min(point.y, drag.start.y),
            w: Math.abs(point.x - drag.start.x),
            h: Math.abs(point.y - drag.start.y),
        });
    });

    document.addEventListener('pointerup', () => {
        if (!drag) {
            return;
        }

        // A click moves the hotspot (centred on the click) and keeps its size
        if (!drag.moved) {
            const inputs = getInputs(drag.widget);
            const w = parseFloat(inputs.w.value) || 4;
            const h = parseFloat(inputs.h.value) || 7;

            write(drag.widget, {
                x: clamp(drag.start.x - w / 2, 0, 100 - w),
                y: clamp(drag.start.y - h / 2, 0, 100 - h),
                w,
                h,
            });
        }

        drag = null;
    });

    document.addEventListener('input', (event) => {
        if (event.target.matches('[data-advent-hotspot-input]')) {
            render(event.target.closest('[data-advent-hotspot]'));
        }
    });

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-advent-hotspot-reset]');

        if (!button) {
            return;
        }

        const widget = button.closest('[data-advent-hotspot]');

        Object.values(getInputs(widget)).forEach((input) => {
            input.value = '';
        });

        render(widget);
    });
})();
