# Public icons

`icons.json` declares the public Lucide names and aliases for old menu icons. The public
bundle and templates have their own styles and sprite, independent of the panel.

After changing the list, regenerate and commit both `public/themes/default/assets/icons/public.svg`
and `themes/default/templates/components/icon-map.twig`:

```sh
npm run icons -- public
```

`npm run icons` builds both public and admin sprites; `npm run icons -- admin` builds only
the admin sprite. All targets use `src/admin/icons/build-sprite.js`.

Render an icon with the public component:

```twig
{% include '@theme/components/icon.twig' with {name: 'bell', size: 20} only %}
```

Icons are decorative by default. Pass a translated `label` for a standalone meaningful icon.
Known names and legacy aliases use the public Lucide sprite. Unknown names, including social
icons, use the old `sprite.svg`, which remains available for module and child-theme icons.

For a module menu item, preserve its existing image detection:

```twig
{% include '@theme/components/icon.twig' with {
    name: item.icon,
    image: item.hasImageIcon,
    size: 24,
    class: 'icon'
} only %}
```

An image keeps its URL and uses the label as its alternative text. An empty name renders nothing.
Neither the public component nor its SCSS imports admin templates or styles.
