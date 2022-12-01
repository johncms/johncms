import 'swiper/css/bundle';

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

import {createApp, defineAsyncComponent} from 'vue'

const app = () => createApp({})

const vue_apps = document.querySelectorAll('.vue_app');
vue_apps.forEach(function (el) {
  const vueApp = app();
  vueApp.component('LikesComponent', defineAsyncComponent(() => import('./components/LikesComponent.vue')));
  vueApp.component('CommentsComponent', defineAsyncComponent(() => import('./components/CommentsComponent.vue')));
  vueApp.component('pagination', defineAsyncComponent(() => import('./components/Pagination/VuePagination.vue')));
  vueApp.component('CkeditorInputComponent', defineAsyncComponent(() => import('./components/CkeditorInputComponent.vue')));
  vueApp.component('AvatarUploader', defineAsyncComponent(() => import('./components/AvatarUploader.vue')));
  vueApp.mount(el);
});
