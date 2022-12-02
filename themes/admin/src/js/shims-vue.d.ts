declare module "*.vue" {
  import type { DefineComponent } from "vue";
  const component: DefineComponent<{}, {}, any>;
  export default component;
}

declare module 'vue/dist/vue.esm-bundler';
declare module 'lodash';
declare module 'bootstrap';
