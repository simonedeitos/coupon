(() => {
  const normalizeTrackUrl = (value) => {
    if (!value || typeof value !== 'string') {
      return '';
    }
    if (value.startsWith('/go/')) {
      return value;
    }
    try {
      const parsed = new URL(value, window.location.origin);
      return parsed.origin === window.location.origin && parsed.pathname.startsWith('/go/')
        ? parsed.toString()
        : '';
    } catch (_) {
      return '';
    }
  };

  const revealCodeInline = (trigger, code) => {
    if (!trigger || !code) {
      return;
    }

    if (trigger.tagName === 'BUTTON') {
      trigger.textContent = code;
      trigger.classList.add('is-code-revealed');
      trigger.setAttribute('aria-label', `Codice coupon ${code}`);
      return;
    }

    const target = trigger.closest('[data-offer-code-container]') || trigger.parentElement;
    if (!target) {
      return;
    }
    let inlineCode = target.querySelector('.inline-coupon-code');
    if (!inlineCode) {
      inlineCode = document.createElement('span');
      inlineCode.className = 'inline-coupon-code';
      target.appendChild(inlineCode);
    }
    inlineCode.textContent = code;
  };

  const modal = document.querySelector('[data-coupon-modal]');
  const codeTarget = modal?.querySelector('[data-coupon-code]');
  const closeModal = () => {
    if (!modal) {
      return;
    }
    modal.hidden = true;
    document.body.classList.remove('modal-open');
  };

  document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-offer-code]');
    if (trigger) {
      const code = trigger.getAttribute('data-offer-code') || '';
      const trackUrl = normalizeTrackUrl(trigger.getAttribute('data-offer-track') || '');

      if (trackUrl) {
        window.open(trackUrl, '_blank', 'noopener,noreferrer');
      }

      revealCodeInline(trigger, code);

      if (modal && codeTarget) {
        codeTarget.textContent = code;
      }

      if (navigator.clipboard?.writeText && code) {
        navigator.clipboard.writeText(code).catch(() => {});
      }
      return;
    }

    if (modal && (event.target.matches('[data-close-modal]') || event.target === modal)) {
      closeModal();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && modal && !modal.hidden) {
      closeModal();
    }
  });

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
      let currentDirectUrl = '';

      if (directLink) {
        directLink.setAttribute('href', '#');
        directLink.addEventListener('click', (event) => {
          if (!currentDirectUrl) {
            event.preventDefault();
            return;
          }
          event.preventDefault();
          window.open(currentDirectUrl, '_blank', 'noopener,noreferrer');
        });
      }

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
            codeButton.textContent = 'Mostra codice';
            codeButton.classList.remove('is-code-revealed');
            codeButton.setAttribute('data-offer-code', offer.code);
            codeButton.setAttribute('data-offer-track', normalizeTrackUrl(offer.track_url || ''));
            directLink.hidden = true;
            currentDirectUrl = '';
          } else {
            directLink.hidden = false;
            currentDirectUrl = normalizeTrackUrl(offer.track_url || '');
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
