/**
 * The mail settings: only the card of the sending method that is chosen is shown — the server of
 * SMTP, the command of sendmail. Without the script every card stays visible.
 */
document.querySelectorAll('[data-mail-settings]').forEach(function (form) {
  const transports = form.querySelectorAll('[data-mail-transport]');
  const cards = form.querySelectorAll('[data-mail-for]');

  function update() {
    const chosen = Array.from(transports).find(item => item.checked);

    cards.forEach(function (card) {
      card.hidden = !chosen || card.dataset.mailFor !== chosen.value;
    });
  }

  transports.forEach(function (item) {
    item.addEventListener('change', update);
  });

  update();
});
