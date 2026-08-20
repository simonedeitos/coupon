<?php
/**
 * Carosello "Offerta del giorno" — mostra le offerte più cliccate della giornata corrente.
 * La rotazione avviene ogni 30 secondi tramite JS vanilla (nessun framework aggiuntivo).
 */
$deals = $dealOfTheDay ?? [];
if (empty($deals)) {
    return;
}
$storeRepo = app('storeRepository');
?>
<section id="offerta-del-giorno" class="deal-of-the-day-section">
    <div class="container">
        <div class="section-head">
            <div>
                <h2>🔥 Offerta del giorno</h2>
                <p>Le promozioni più cliccate oggi — si aggiornano in tempo reale.</p>
            </div>
        </div>
        <div class="dotd-carousel" data-dotd-carousel aria-live="polite">
            <?php foreach ($deals as $index => $offer): ?>
                <?php $store = $storeRepo->findById((int) $offer['store_id']); ?>
                <?php $discount = \App\Helpers\OfferHelper::formatDiscount($offer); ?>
                <div class="dotd-slide<?php echo $index === 0 ? ' active' : ''; ?>"
                     data-slide="<?php echo $index; ?>"
                     aria-hidden="<?php echo $index === 0 ? 'false' : 'true'; ?>">
                    <div class="dotd-card">
                        <div class="dotd-store">
                            <div class="store-logo-circle"><?php echo e($store['initial'] ?? '?'); ?></div>
                            <div>
                                <strong><?php echo e($store['name'] ?? 'Store'); ?></strong>
                                <small><?php echo e(\App\Helpers\OfferHelper::getOfferTypeLabel((string) $offer['type'])); ?></small>
                            </div>
                        </div>
                        <?php if ($discount !== ''): ?>
                            <div class="dotd-badge"><?php echo e($discount); ?></div>
                        <?php endif; ?>
                        <h3 class="dotd-title"><?php echo e($offer['title']); ?></h3>
                        <?php if (! empty($offer['description'])): ?>
                            <p class="dotd-desc"><?php echo e($offer['description']); ?></p>
                        <?php endif; ?>
                        <div class="dotd-actions">
                            <?php if (! empty($offer['code'])): ?>
                                <button class="btn redeem"
                                        type="button"
                                        data-offer-code="<?php echo e($offer['code']); ?>"
                                        data-offer-track="<?php echo e(url('/go/' . $offer['id'])); ?>">
                                    Mostra codice
                                </button>
                            <?php else: ?>
                                <a class="btn"
                                   href="<?php echo e(url('/go/' . $offer['id'])); ?>"
                                   target="_blank"
                                   rel="nofollow sponsored noopener noreferrer">
                                    Vai all'offerta
                                </a>
                            <?php endif; ?>
                            <a class="btn btn-secondary" href="<?php echo e(url('/coupon/' . $offer['slug'])); ?>">Dettagli</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (count($deals) > 1): ?>
        <div class="dotd-indicators" aria-hidden="true">
            <?php foreach ($deals as $index => $deal): ?>
                <button class="dotd-dot<?php echo $index === 0 ? ' active' : ''; ?>"
                        data-target="<?php echo $index; ?>"
                        aria-label="Offerta <?php echo $index + 1; ?>"></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
(function () {
    const carousel = document.querySelector('[data-dotd-carousel]');
    if (!carousel) return;

    const slides = carousel.querySelectorAll('.dotd-slide');
    const dots = document.querySelectorAll('.dotd-dot');
    let current = 0;
    const total = slides.length;
    if (total < 2) return;

    function goTo(index) {
        slides[current].classList.remove('active');
        slides[current].setAttribute('aria-hidden', 'true');
        if (dots[current]) dots[current].classList.remove('active');

        current = (index + total) % total;

        slides[current].classList.add('active');
        slides[current].setAttribute('aria-hidden', 'false');
        if (dots[current]) dots[current].classList.add('active');
    }

    // Rotazione automatica ogni 30 secondi
    let timer = setInterval(() => goTo(current + 1), 30000);

    // Click sui dot indicatori
    dots.forEach((dot) => {
        dot.addEventListener('click', () => {
            clearInterval(timer);
            goTo(parseInt(dot.dataset.target, 10));
            timer = setInterval(() => goTo(current + 1), 30000);
        });
    });
})();
</script>
