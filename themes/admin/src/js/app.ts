require('./bootstrap');
require('./jquery.magnific-popup');
require("flatpickr");
require('./menu');
require('./prism');
require('./modals');
require('./main');

import {createApp, defineAsyncComponent} from 'vue'

const app = () => createApp({})

const vue_apps = document.querySelectorAll('.vue_app');
vue_apps.forEach(function (el) {
  let vueApp = app();
  vueApp.component('CkeditorInputComponent', defineAsyncComponent(() => import('@/components/CkeditorInputComponent.vue')));
  vueApp.mount(el);
});
