// A popover marked data-hover-popover also opens while the mouse is over it, so a glance needs no
// click; a click keeps it open until a click elsewhere. On a touch screen it is an ordinary popover.
const CLOSE_DELAY = 200;

if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
    for (const popover of document.querySelectorAll('[data-hover-popover]')) setUp(popover);
    // Comments and other lists are swapped in place by the live list: their popovers are new.
    document.addEventListener('live-list:update', () => {
        for (const popover of document.querySelectorAll('[data-hover-popover]')) setUp(popover);
    });
}

function setUp(popover) {
    if (popover.dataset.hoverReady) return;
    popover.dataset.hoverReady = '1';
    // Opened by the mouse alone: leaving closes it, and a click turns it into an opened one.
    let hovered = false;
    let timer;

    popover.addEventListener('mouseenter', () => {
        clearTimeout(timer);
        if (popover.open) return;
        hovered = true;
        popover.open = true;
    });
    popover.addEventListener('mouseleave', () => {
        if (!hovered) return;
        timer = setTimeout(() => {
            hovered = false;
            popover.open = false;
        }, CLOSE_DELAY);
    });
    popover.querySelector('summary').addEventListener('click', event => {
        if (!hovered) return;
        event.preventDefault();
        hovered = false;
    });
    popover.addEventListener('toggle', () => {
        if (!popover.open) hovered = false;
    });
}
