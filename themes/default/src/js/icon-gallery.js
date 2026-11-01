/** Read names from the sprite so the searchable gallery cannot fall behind the icon set. */
document.querySelectorAll('[data-site-icon-gallery]').forEach(async function (gallery) {
  const search = gallery.querySelector('input[type="search"]');
  const grid = gallery.querySelector('[data-icon-grid]');
  const status = gallery.querySelector('[data-icon-status]');

  try {
    const response = await fetch(gallery.dataset.sprite);
    if (!response.ok) {
      throw new Error(`Could not load the icon sprite: HTTP ${response.status}`);
    }
    const sprite = new DOMParser().parseFromString(await response.text(), 'image/svg+xml');
    const fragment = document.createDocumentFragment();

    sprite.querySelectorAll('symbol[id]').forEach(function ({ id }) {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'site-guide-icons__item';
      button.title = gallery.dataset.copyLabel;
      button.dataset.iconName = id;

      const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
      icon.classList.add('site-icon', 'site-icon--lucide');
      icon.setAttribute('width', '20');
      icon.setAttribute('height', '20');
      icon.setAttribute('aria-hidden', 'true');
      const use = document.createElementNS('http://www.w3.org/2000/svg', 'use');
      use.setAttribute('href', `${gallery.dataset.sprite}#${id}`);
      icon.append(use);

      const label = document.createElement('span');
      label.className = 'site-guide-icons__name';
      label.textContent = id;
      button.append(icon, label);
      fragment.append(button);
    });
    grid.append(fragment);

    const filter = () => {
      const query = search.value.trim().toLowerCase();
      grid.querySelectorAll('[data-icon-name]').forEach(button => {
        button.hidden = !button.dataset.iconName.includes(query);
      });
    };
    search.addEventListener('input', filter);
    filter();

    grid.addEventListener('click', async function (event) {
      const button = event.target.closest('[data-icon-name]');
      if (!button) {
        return;
      }
      const name = button.dataset.iconName;
      try {
        await navigator.clipboard.writeText(`{% include '@theme/components/icon.twig' with {name: '${name}'} only %}`);
        status.classList.remove('text-danger');
        status.textContent = `${gallery.dataset.copiedLabel}: ${name}`;
      } catch (error) {
        status.textContent = gallery.dataset.errorMessage;
        status.classList.add('text-danger');
        console.error('Could not copy the icon include.', error);
      }
    });
  } catch (error) {
    status.textContent = gallery.dataset.loadErrorMessage;
    status.classList.add('text-danger');
    console.error('Could not initialize the icon gallery.', error);
  }
});
