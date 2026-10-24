<template>
  <div class="admin-guide-icons">
    <input v-model="query" type="search" class="form-control admin-guide-icons__search" :placeholder="searchLabel" :aria-label="searchLabel">
    <div class="admin-guide-icons__grid">
      <button
        v-for="name in shown"
        :key="name"
        type="button"
        class="admin-guide-icons__item"
        :title="copyLabel"
        @click="copy(name)"
      >
        <svg class="admin-icon" width="20" height="20" aria-hidden="true">
          <use :href="sprite + '#' + name"/>
        </svg>
        <span class="admin-guide-icons__name">{{ copied === name ? copiedLabel : name }}</span>
      </button>
    </div>
  </div>
</template>

<script>
/**
 * Every icon of the admin sprite, read from the sprite itself, so the list cannot fall behind
 * it. A click copies the include that draws the icon.
 */
export default {
  name: "IconGallery",
  props: {
    sprite: {
      type: String,
      required: true
    },
    searchLabel: {
      type: String,
      default: 'Search'
    },
    copyLabel: {
      type: String,
      default: 'Copy'
    },
    copiedLabel: {
      type: String,
      default: 'Copied'
    },
  },
  data()
  {
    return {
      names: [],
      query: '',
      copied: null,
    }
  },
  computed: {
    shown()
    {
      const query = this.query.trim().toLowerCase();

      return query === '' ? this.names : this.names.filter(name => name.includes(query));
    },
  },
  mounted()
  {
    fetch(this.sprite)
      .then(response => response.text())
      .then(source => {
        const sprite = new DOMParser().parseFromString(source, 'image/svg+xml');
        this.names = Array.from(sprite.querySelectorAll('symbol[id]'), symbol => symbol.id);
      })
      .catch(error => console.error(error));
  },
  methods: {
    copy(name)
    {
      const include = "{% include '@admin/components/ui/icon.twig' with {name: '" + name + "'} only %}";

      navigator.clipboard?.writeText(include).then(() => {
        this.copied = name;
        setTimeout(() => this.copied = null, 1200);
      });
    },
  },
}
</script>
