<?php $storeIndex = $storesById ?? array_column(app('storeRepository')->all(), null, 'id'); ?>
<?php if (empty($offers)): ?>
<div class="empty-state">Nessuna offerta disponibile oggi.</div>
<?php return; endif; ?>
<div class="today-carousel" id="todayCarousel">
    <div class="today-carousel-track" id="todayCarouselTrack">
        <?php foreach ($offers as $i => $offer): ?>
            <?php $store = $storeIndex[(int) $offer['store_id']] ?? null; ?>
            <?php $discountLabel = \App\Helpers\OfferHelper::formatDiscount($offer); ?>
            <div class="today-slide<?php echo $i === 0 ? ' active' : ''; ?>" data-index="<?php echo $i; ?>">
                <div class="today-slide-inner">
                    <div class="today-slide-store">
                        <div class="mini-logo"><?php echo e($store['initial'] ?? '?'); ?></div>
                        <div>
                            <strong><?php echo e($store['name'] ?? 'Store'); ?></strong>
                            <small><?php echo e(\App\Helpers\OfferHelper::getOfferTypeLabel((string) $offer['type'])); ?></small>
                        </div>
                    </div>
                    <div class="today-slide-content">
                        <h3><a href="<?php echo e(url('/coupon/' . $offer['slug'])); ?>"><?php echo e($offer['title']); ?></a></h3>
                        <?php if ($discountLabel !== ''): ?>
                            <span class="badge-discount"><?php echo e($discountLabel); ?></span>
                        <?php endif; ?>
                        <p class="today-desc"><?php echo e($offer['description'] ?? ''); ?></p>
                    </div>
                    <div class="today-slide-cta">
                        <?php if (! empty($offer['code'])): ?>
                            <button class="btn" type="button"
                                data-offer-code="<?php echo e($offer['code']); ?>"
                                data-offer-track="<?php echo e(url('/go/' . $offer['id'])); ?>">
                                Mostra codice
                            </button>
                        <?php else: ?>
                            <a class="btn" href="<?php echo e(url('/go/' . $offer['id'])); ?>"
                                rel="nofollow sponsored noopener noreferrer" target="_blank">
                                Vai all'offerta
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if (count($offers) > 1): ?>
    <button class="carousel-arrow carousel-prev" id="todayPrev" aria-label="Precedente">&#8249;</button>
    <button class="carousel-arrow carousel-next" id="todayNext" aria-label="Successivo">&#8250;</button>
    <div class="carousel-dots" id="todayDots">
        <?php foreach ($offers as $i => $offer): ?>
            <button class="carousel-dot<?php echo $i === 0 ? ' active' : ''; ?>" data-index="<?php echo $i; ?>" aria-label="Offerta <?php echo $i + 1; ?>"></button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<script>
(function () {
    var carousel = document.getElementById('todayCarousel');
    if (!carousel) return;
    var slides = carousel.querySelectorAll('.today-slide');
    var dots = carousel.querySelectorAll('.carousel-dot');
    var current = 0;
    var timer = null;
    var paused = false;

    function goTo(index) {
        slides[current].classList.remove('active');
        if (dots[current]) dots[current].classList.remove('active');
        current = (index + slides.length) % slides.length;
        slides[current].classList.add('active');
        if (dots[current]) dots[current].classList.add('active');
    }

    function startTimer() {
        clearInterval(timer);
        timer = setInterval(function () {
            if (!paused) goTo(current + 1);
        }, 30000);
    }

    carousel.addEventListener('mouseenter', function () { paused = true; });
    carousel.addEventListener('mouseleave', function () { paused = false; });

    var prevBtn = document.getElementById('todayPrev');
    var nextBtn = document.getElementById('todayNext');
    if (prevBtn) prevBtn.addEventListener('click', function () { goTo(current - 1); startTimer(); });
    if (nextBtn) nextBtn.addEventListener('click', function () { goTo(current + 1); startTimer(); });

    dots.forEach(function (dot) {
        dot.addEventListener('click', function () { goTo(parseInt(dot.dataset.index, 10)); startTimer(); });
    });

    startTimer();
})();
</script>
