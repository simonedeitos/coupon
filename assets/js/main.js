(() => {
  const modal = document.querySelector('[data-coupon-modal]');
  const codeTarget = modal?.querySelector('[data-coupon-code]');
  if (!modal || !codeTarget) {
    return;
  }

  const closeModal = () => {
    modal.hidden = true;
    document.body.classList.remove('modal-open');
  };

  const openModal = async (code, trackUrl) => {
    codeTarget.textContent = code;
    modal.hidden = false;
    document.body.classList.add('modal-open');

    if (navigator.clipboard?.writeText) {
      try { await navigator.clipboard.writeText(code); } catch (error) {}
    }

    // Apre il link del negozio (redirect di tracciamento /go/{id}) in una nuova scheda,
    // così il click viene registrato dal network di affiliazione (es. TradeDoubler).
    if (trackUrl) {
      window.open(trackUrl, '_blank', 'noopener');
    }
  };

  document.addEventListener('click', async (event) => {
    const trigger = event.target.closest('[data-offer-code]');
    if (trigger) {
      const code = trigger.getAttribute('data-offer-code') || '';
      const trackUrl = trigger.getAttribute('data-offer-track') || '';
      await openModal(code, trackUrl);
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