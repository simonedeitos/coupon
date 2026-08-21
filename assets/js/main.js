(() => {
  const modal = document.querySelector('[data-coupon-modal]');
  const codeTarget = modal?.querySelector('[data-coupon-code]');
  if (modal && codeTarget) {
    const closeModal = () => {
      modal.hidden = true;
      document.body.classList.remove('modal-open');
    };

    const openModal = (code, trackUrl) => {
      if (trackUrl) {
        window.open(trackUrl, '_blank', 'noopener,noreferrer');
      }

      codeTarget.textContent = code;
      modal.hidden = false;
      document.body.classList.add('modal-open');

      if (navigator.clipboard?.writeText) {
        navigator.clipboard.writeText(code).catch(() => {});
      }
    };

    document.addEventListener('click', (event) => {
      const trigger = event.target.closest('[data-offer-code]');
      if (trigger) {
        const code = trigger.getAttribute('data-offer-code') || '';
        const trackUrl = trigger.getAttribute('data-offer-track') || '';
        openModal(code, trackUrl);
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
  }

  const heroRotator = document.querySelector('[data-hero-offer-rotator]');
  if (heroRotator) {
    const offersJson = heroRotator.getAttribute('data-hero-offers') || '[]';
    let offers = [];
    try {
      offers = JSON.parse(offersJson);
    } catch (_) {
      offers = [];
    }
    if (offers.length > 1) {
      const storeTarget = heroRotator.querySelector('[data-hero-store-name]');
      const typeTarget = heroRotator.querySelector('[data-hero-offer-type]');
      const discountTarget = heroRotator.querySelector('[data-hero-discount]');
      const titleTarget = heroRotator.querySelector('[data-hero-title]');
      const descTarget = heroRotator.querySelector('[data-hero-description]');
      const codeButton = heroRotator.querySelector('[data-hero-code-button]');
      const directLink = heroRotator.querySelector('[data-hero-direct-link]');
      let index = 0;

      const render = (next) => {
        const offer = offers[next];
        if (!offer) {
          return;
        }
        if (storeTarget) storeTarget.textContent = offer.store_name || 'Store';
        if (typeTarget) typeTarget.textContent = offer.type_label || '';
        if (discountTarget) {
          if (offer.discount_label) {
            discountTarget.textContent = offer.discount_label;
            discountTarget.hidden = false;
          } else {
            discountTarget.textContent = '';
            discountTarget.hidden = true;
          }
        }
        if (titleTarget) titleTarget.textContent = offer.title || '';
        if (descTarget) descTarget.textContent = offer.description || '';
        if (codeButton && directLink) {
          if (offer.code) {
            codeButton.hidden = false;
            codeButton.setAttribute('data-offer-code', offer.code);
            codeButton.setAttribute('data-offer-track', offer.track_url || '');
            directLink.hidden = true;
          } else {
            directLink.hidden = false;
            directLink.setAttribute('href', offer.track_url || '#');
            codeButton.hidden = true;
            codeButton.removeAttribute('data-offer-code');
            codeButton.removeAttribute('data-offer-track');
          }
        }
      };

      setInterval(() => {
        index = (index + 1) % offers.length;
        render(index);
      }, 30000);
    }
  }
})();