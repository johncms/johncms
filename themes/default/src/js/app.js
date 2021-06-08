/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

import Vue from "vue";

require('./bootstrap');
require('./jquery.magnific-popup');
require("flatpickr");
require('./menu');
require('./prism');
require('./forum');
require('./modals');
require('./slider');
require('./progress');
require('./wysibb');
require('./main');

/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Автозагрузка компонентов
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */
const files = require.context('./', true, /\.vue$/i)
files.keys().map(key => Vue.component(key.split('/').pop().split('.')[0], files(key).default))

Vue.component('pagination', require('laravel-vue-pagination'));

const vue_apps = document.querySelectorAll('.vue_app');
vue_apps.forEach(function (el) {
    new Vue({
        el: el,
    });
});
