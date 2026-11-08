/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

// Must stay first: it publishes jQuery, axios and lodash as globals.
import './bootstrap';
// Self-hosted fonts of the design (SIL OFL 1.1, see THIRD-PARTY-NOTICES.md). Every script is
// declared with its unicode-range, so a browser downloads only the ones a page uses.
// The optical size axis lets small text open up its spacing for the screen.
import '@fontsource-variable/inter/opsz.css';
import '@fontsource-variable/jetbrains-mono';
import '../scss/app.scss';

import './jquery.magnific-popup';
import './menu';
import './preferences';
import './toast';
import './confirm';
import './editor';
import './live-list';
import './news';

if (document.querySelector('[data-site-icon-gallery]')) {
    import('./icon-gallery');
}
if (document.querySelector('[data-site-palette]')) {
    import('./palette-contrast');
}

import './prism';
import './forum';
import './modals';
import './slider';
import './progress';
import './main';

import { createApp } from 'vue';

// Prism is loaded in manual mode, see ./bootstrap. Code swapped in by a live list is highlighted
// on arrival.
window.Prism.highlightAll();
document.addEventListener('live-list:update', event => window.Prism.highlightAllUnder(event.target));

/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Автозагрузка компонентов
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */
const components = import.meta.glob('./components/**/*.vue', { eager: true });

/**
 * The site is server rendered, so Vue is mounted as separate islands: every
 * .vue_app element becomes its own application using the markup as a template.
 */
document.querySelectorAll('.vue_app').forEach(function (el) {
    const app = createApp({});

    Object.entries(components).forEach(function ([path, component]) {
        app.component(path.split('/').pop().replace(/\.vue$/, ''), component.default);
    });

    app.mount(el);
});
