/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

// Must stay first: it publishes jQuery, axios and lodash as globals.
import './bootstrap';
import '../scss/app.scss';

import './jquery.magnific-popup';
import './menu';
import './prism';
import './forum';
import './modals';
import './slider';
import './progress';
import './main';

import { createApp } from 'vue';
import { Bootstrap5Pagination } from 'laravel-vue-pagination';

// Prism is loaded in manual mode, see ./bootstrap.
window.Prism.highlightAll();

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

    app.component('pagination', Bootstrap5Pagination);
    app.mount(el);
});
