import { Tooltip } from 'bootstrap';

// Elements marked data-bs-toggle="tooltip" show their title as a Bootstrap tooltip.
// Comments and other lists are swapped in place by the live list: their tooltips are new.
function setUp(root) {
    for (const element of root.querySelectorAll('[data-bs-toggle="tooltip"]')) Tooltip.getOrCreateInstance(element);
}

setUp(document);
document.addEventListener('live-list:update', event => setUp(event.target));
