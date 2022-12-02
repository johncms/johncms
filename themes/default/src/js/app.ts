import 'swiper/css/bundle';

import './bootstrap';
import '../scss/app.scss';

// import './jquery.magnific-popup';
import 'flatpickr';
import './menu';
// import './prism';
import './forum';
import './modals';
import './slider';
import './progress';
// import './wysibb';
import './main';

import {createApp, defineAsyncComponent} from 'vue/dist/vue.esm-bundler'

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
