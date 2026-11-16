// The label of a file field says which file was chosen. Taken on the whole page, as the field may
// come later; the name is set as text, it is the visitor's own.
document.addEventListener('change', event => {
  const input = event.target;
  if (!(input instanceof HTMLInputElement) || !input.classList.contains('custom-file-input')) {
    return;
  }
  const label = input.parentElement?.querySelector('.custom-file-label');
  if (label) {
    label.classList.add('selected');
    label.textContent = input.files?.[0]?.name ?? '';
  }
});
