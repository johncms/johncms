/**
 * A button that copies the value of a field: data-copy names the field by a selector. The icon
 * turns into a tick for a moment so that the copy is seen to have happened.
 */
document.addEventListener('click', function (event) {
  const button = event.target.closest('[data-copy]');
  if (!button) {
    return;
  }

  const field = document.querySelector(button.dataset.copy);
  if (!field || !navigator.clipboard) {
    return;
  }

  navigator.clipboard.writeText(field.value).then(function () {
    button.classList.add('is-copied');
    setTimeout(function () {
      button.classList.remove('is-copied');
    }, 1500);
  });
});
