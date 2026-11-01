/**
 * The WCAG contrast of every text/fill pair of the guide's palette, measured on the colors the
 * browser has resolved, so it follows the tokens and the current scheme.
 */
const canvas = document.createElement('canvas');
canvas.width = canvas.height = 1;
const context = canvas.getContext('2d', {willReadFrequently: true});

// Any CSS color (oklch, color-mix) as sRGB channels, read back from a one-pixel canvas.
function channels(color) {
  context.clearRect(0, 0, 1, 1);
  context.fillStyle = color;
  context.fillRect(0, 0, 1, 1);
  return [...context.getImageData(0, 0, 1, 1).data.slice(0, 3)];
}

function luminance(color) {
  const [r, g, b] = channels(color).map(value => {
    const c = value / 255;
    return c <= 0.04045 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;
  });
  return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

function measure(palette) {
  palette.querySelectorAll('[data-contrast-pair]').forEach(item => {
    const style = getComputedStyle(item);
    const [light, dark] = [luminance(style.color), luminance(style.backgroundColor)].sort((a, b) => b - a);
    const ratio = (light + 0.05) / (dark + 0.05);
    const level = ratio >= 7 ? 'AAA' : ratio >= 4.5 ? 'AA' : ratio >= 3 ? 'AA large' : 'fail';
    const output = item.querySelector('[data-contrast]');
    output.textContent = `${ratio.toFixed(2)} · ${level}`;
    item.classList.toggle('site-guide-palette__item--fail', ratio < 4.5);
  });
}

document.querySelectorAll('[data-site-palette]').forEach(palette => {
  const update = () => measure(palette);
  update();
  // The scheme changes through the class of body or, for "auto", through the system setting.
  new MutationObserver(update).observe(document.body, {attributes: true, attributeFilter: ['class']});
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', update);
});
