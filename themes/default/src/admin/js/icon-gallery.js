/** Read names from the sprite so the searchable gallery cannot fall behind the icon set. */
document.querySelectorAll('[data-admin-icon-gallery]').forEach(async function (gallery) {
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
      button.className = 'admin-guide-icons__item';
      button.title = gallery.dataset.copyLabel;
      button.dataset.iconName = id;

      const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
      icon.classList.add('admin-icon');
      icon.setAttribute('width', '20');
      icon.setAttribute('height', '20');
      icon.setAttribute('aria-hidden', 'true');
      const use = document.createElementNS('http://www.w3.org/2000/svg', 'use');
      use.setAttribute('href', `${gallery.dataset.sprite}#${id}`);
      icon.append(use);

      const label = document.createElement('span');
      label.className = 'admin-guide-icons__name';
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
      if (!button || !navigator.clipboard) {
        return;
      }
      const name = button.dataset.iconName;
      try {
        await navigator.clipboard.writeText(`{% include '@admin/components/ui/icon.twig' with {name: '${name}'} only %}`);
        const label = button.querySelector('.admin-guide-icons__name');
        label.textContent = gallery.dataset.copiedLabel;
        setTimeout(() => { label.textContent = name; }, 1200);
      } catch (error) {
        status.textContent = gallery.dataset.errorMessage;
        status.hidden = false;
        console.error('Could not copy the icon include.', error);
      }
    });
  } catch (error) {
    status.textContent = gallery.dataset.errorMessage;
    status.hidden = false;
    console.error('Could not initialize the icon gallery.', error);
  }
});
