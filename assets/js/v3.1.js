/* CargaPrime — interações da home: vídeos, diálogos e atualização automática do carrinho.
 * O catálogo completo e respetivos filtros/paginação são geridos em products.php + catalog.js.
 */

const silenceOtherVideos = (except) => {
  document.querySelectorAll('video').forEach((video) => { if (video !== except) video.muted = true; });
  document.querySelectorAll('.cp3-video-sound,.cp3-hero-sound').forEach((button) => {
    const controls = button.closest('.cp3-video,.cp3-hero')?.querySelector('video');
    if (controls !== except) {
      button.setAttribute('aria-pressed', 'false');
      const label = button.querySelector('span:last-child');
      if (label) label.textContent = 'Ativar som';
    }
  });
};

document.addEventListener('click', async (event) => {
  const soundButton = event.target.closest('.cp3-video-sound,.cp3-hero-sound');
  if (soundButton) {
    const video = soundButton.closest('.cp3-video,.cp3-hero')?.querySelector('video');
    if (!video) return;
    const enable = soundButton.getAttribute('aria-pressed') !== 'true';
    silenceOtherVideos(enable ? video : null);
    video.muted = !enable;
    soundButton.setAttribute('aria-pressed', String(enable));
    const label = soundButton.querySelector('span:last-child');
    if (label) label.textContent = enable ? 'Silenciar' : 'Ativar som';
    if (enable) await video.play().catch(() => {});
    return;
  }

  const opener = event.target.closest('[data-dialog-open]');
  if (opener) {
    document.getElementById(opener.dataset.dialogOpen)?.showModal();
    return;
  }
  const closer = event.target.closest('[data-dialog-close]');
  if (closer) closer.closest('dialog')?.close();
});

document.addEventListener('click', (event) => {
  const dialog = event.target.closest('dialog');
  if (dialog && event.target === dialog) dialog.close();
});

document.addEventListener('change', (event) => {
  const imageInput = event.target.closest('[data-image-input]');
  if (imageInput) {
    const file = imageInput.files?.[0];
    const editor = imageInput.closest('.image-editor');
    const preview = editor?.querySelector('[data-image-preview]');
    if (file && preview) {
      preview.src = URL.createObjectURL(file);
      preview.hidden = false;
      editor.querySelector('[data-image-placeholder]')?.setAttribute('hidden', '');
    }
  }

  const quantity = event.target.closest('.cart-actions input[name="quantity"]');
  if (quantity && quantity.checkValidity()) {
    const form = quantity.closest('form');
    clearTimeout(form.cpQuantityTimer);
    form.cpQuantityTimer = setTimeout(() => form.requestSubmit(form.querySelector('button[value="update"]')), 350);
  }
});
