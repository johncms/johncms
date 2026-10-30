/**
 * The languages of the site: the default language is always on the site, so its switch is locked
 * on, and choosing another default turns that one on and frees the previous one.
 */
document.querySelectorAll('[data-language-settings]').forEach(function (form) {
  const rows = Array.from(form.querySelectorAll('tr')).filter(row => row.querySelector('[data-language-default]'));

  function update() {
    rows.forEach(function (row) {
      const isDefault = row.querySelector('[data-language-default]').checked;
      const enabled = row.querySelector('[data-language-enabled]');

      if (isDefault) {
        enabled.checked = true;
      }
      enabled.disabled = isDefault;
    });
  }

  form.querySelectorAll('[data-language-default]').forEach(function (radio) {
    radio.addEventListener('change', update);
  });
});
