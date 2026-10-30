/**
 * The form of an advertising link: the color picker follows the hex field and back, and the
 * preview shows the first title with the chosen color and style.
 */
document.querySelectorAll('[data-ad-form]').forEach(function (form) {
  const name = form.querySelector('[data-ad-name]');
  const color = form.querySelector('[data-ad-color]');
  const picker = form.querySelector('[data-ad-color-picker]');
  const preview = form.querySelector('[data-ad-preview]');
  const styles = form.querySelectorAll('[data-ad-style]');
  const hex = /^[\da-f]{6}$/i;
  const classes = {bold: 'fw-bold', italic: 'fst-italic', underline: 'text-decoration-underline'};

  function update() {
    const title = name.value.split('|').map(part => part.trim()).find(part => part !== '');
    const value = color.value.trim().replace(/^#/, '');

    preview.textContent = title || preview.dataset.empty;
    preview.style.color = hex.test(value) ? '#' + value : '';

    if (hex.test(value)) {
      picker.value = '#' + value.toLowerCase();
    }

    styles.forEach(function (style) {
      preview.classList.toggle(classes[style.dataset.adStyle], style.checked);
    });
  }

  picker.hidden = false;
  picker.addEventListener('input', function () {
    color.value = picker.value.slice(1).toUpperCase();
    update();
  });

  [name, color, ...styles].forEach(function (field) {
    field.addEventListener('input', update);
    field.addEventListener('change', update);
  });

  update();
});
