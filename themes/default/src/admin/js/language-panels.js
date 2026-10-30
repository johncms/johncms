/**
 * Show the fields of the language chosen in a language switch (components/ui/language-switch.twig)
 * and hide those of the others: every [data-language-panel] of the same form.
 */
document.querySelectorAll('[data-language-switch]').forEach(function (switcher) {
  const form = switcher.closest('form') || document;
  const panels = form.querySelectorAll('[data-language-panel]');
  const value = switcher.querySelector('[data-language-value]');
  const label = switcher.querySelector('[data-language-label]');
  const checkTemplate = switcher.querySelector('template[data-language-check]');
  const options = switcher.querySelectorAll('[data-language-option]');

  function showLanguage(code) {
    const option = Array.from(options).find(item => item.dataset.languageCode === code);

    if (!option) {
      return;
    }

    if (value) {
      value.value = code;
    }
    label.textContent = option.dataset.languageLabel;

    panels.forEach(function (panel) {
      panel.hidden = panel.dataset.languagePanel !== code;
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

  const active = Array.from(options).find(item => item.classList.contains('active'));
  if (active) {
    showLanguage(active.dataset.languageCode);
  }
});
