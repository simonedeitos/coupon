(() => {
  const modal = document.querySelector('[data-coupon-modal]');
  const codeTarget = modal?.querySelector('[data-coupon-code]');
  if (!modal || !codeTarget) {
    return;
  }

  let autoCloseTimer = null;
  const MODAL_AUTO_CLOSE_MS = 2000;

  const closeModal = () => {
    clearTimeout(autoCloseTimer);
    modal.hidden = true;
    document.body.classList.remove('modal-open');
  };

  const openModal = async (code) => {
    clearTimeout(autoCloseTimer);
    codeTarget.textContent = code;
    modal.hidden = false;
    document.body.classList.add('modal-open');
    if (navigator.clipboard?.writeText) {
      try { await navigator.clipboard.writeText(code); } catch (error) {}
    }
    autoCloseTimer = setTimeout(closeModal, MODAL_AUTO_CLOSE_MS);
  };

  document.addEventListener('click', async (event) => {
    const trigger = event.target.closest('[data-offer-code]');
    if (trigger) {
      const code = trigger.getAttribute('data-offer-code') || '';
      await openModal(code);
      return;
    }

    if (event.target.matches('[data-close-modal]') || event.target === modal) {
      closeModal();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !modal.hidden) {
      closeModal();
    }
  });
})();
