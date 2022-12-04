import './bootstrap';
import '../scss/app.scss';
import './menu';
// import './prism';
import './modals';
import './main';


// @ts-ignore
import {createApp, defineAsyncComponent} from 'vue/dist/vue.esm-bundler'
import {createI18n} from 'vue-i18n'
import en from './locale/en.json';
import ru from './locale/ru.json';

const i18n = createI18n({
  locale: document.documentElement.lang,
  fallbackLocale: 'en',
  messages: {
    en: en,
    ru: ru,
  },
})

const app = () => createApp({})

const vue_apps = document.querySelectorAll('.vue_app');
vue_apps.forEach(function (el) {
  let vueApp = app();
  vueApp.use(i18n);
  vueApp.component('CkeditorInputComponent', defineAsyncComponent(() => import('./components/CkeditorInputComponent.vue')));
  vueApp.component('Pagination', defineAsyncComponent(() => import('./components/Pagination/VuePagination.vue')));
  vueApp.component('UserList', defineAsyncComponent(() => import('./views/Users/UserList.vue')));
  vueApp.mount(el);
});
