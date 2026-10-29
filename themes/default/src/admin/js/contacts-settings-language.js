/**
 * Show the translated contact settings for the language selected above the fields.
 */
document.querySelectorAll('[data-contacts-language-switch]').forEach(function (switcher) {
  const form = switcher.closest('form');
  const panels = form.querySelectorAll('[data-contacts-language-panel]');
  const value = switcher.querySelector('[data-contacts-language-value]');
  const label = switcher.querySelector('[data-contacts-language-label]');
  const checkTemplate = switcher.querySelector('template[data-contacts-language-check]');
  const options = switcher.querySelectorAll('[data-contacts-language-option]');

  function showLanguage(code) {
    const option = Array.from(options).find(item => item.dataset.languageCode === code);

    if (!option) {
      return;
    }

    value.value = code;
    label.textContent = option.dataset.languageLabel;

    panels.forEach(function (panel) {
      panel.hidden = panel.id !== 'contacts-language-' + code;
    });

    options.forEach(function (item) {
      const active = item === option;
      const check = item.querySelector('.admin-filter__check');

      item.classList.toggle('active', active);
      item.setAttribute('aria-pressed', active ? 'true' : 'false');
      check.replaceChildren();

      if (active) {
        check.append(checkTemplate.content.cloneNode(true));
      }
    });
  }

  options.forEach(function (option) {
    option.addEventListener('click', function () {
      showLanguage(option.dataset.languageCode);
    });
  });

  showLanguage(value.value);
});
