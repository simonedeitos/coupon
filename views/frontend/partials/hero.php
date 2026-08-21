<?php
$heroOffers = ! empty($todayOffers) ? $todayOffers : $offers;
$heroOffer = $heroOffers[0] ?? null;
$heroStore = null;
$heroDiscountLabel = '';
$heroSlides = [];
if ($heroOffer !== null) {
    $heroStore = $storesById[(int) $heroOffer['store_id']] ?? app('storeRepository')->findById((int) $heroOffer['store_id']);
    $heroDiscountLabel = \App\Helpers\OfferHelper::formatDiscount($heroOffer);
}
foreach ($heroOffers as $offerItem) {
    $offerStore = $storesById[(int) $offerItem['store_id']] ?? app('storeRepository')->findById((int) $offerItem['store_id']);
    $heroSlides[] = [
        'title' => (string) ($offerItem['title'] ?? ''),
        'description' => (string) ($offerItem['description'] ?? ''),
        'code' => (string) ($offerItem['code'] ?? ''),
        'track_url' => (string) url('/go/' . $offerItem['id']),
        'discount_label' => \App\Helpers\OfferHelper::formatDiscount($offerItem),
        'store_name' => (string) ($offerStore['name'] ?? 'Store'),
        'type_label' => \App\Helpers\OfferHelper::getOfferTypeLabel((string) ($offerItem['type'] ?? '')),
    ];
}
?>
<section class="hero">
    <div class="container hero-inner">
        <div>
            <form class="search search-compact hero-search-top" action="<?php echo e(url('/cerca')); ?>" method="get">
                <span class="search-icon">⌕</span>
                <input type="search" name="q" placeholder="Cerca coupon, negozio o categoria..." aria-label="Cerca coupon">
                <button type="submit">Cerca</button>
            </form>
            <div class="category-filters category-filters-top">
                <?php foreach (array_slice($categories, 0, 10) as $category): ?>
                    <a class="pill" href="<?php echo e(url('/categoria/' . $category['slug'])); ?>">
                        <span class="category-pill-icon"><?php echo e($category['icon']); ?></span>
                        <span><?php echo e($category['name']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="eyebrow">✦ Risparmia ad ogni acquisto</div>
            <h1>Trova il tuo prossimo <em>affare.</em></h1>
            <p>Codici sconto, coupon e offerte dei tuoi negozi preferiti. Cerca, scegli e risparmia in pochi secondi.</p>
            <div class="hero-stats">
                <div class="stat"><strong><?php echo e($stats['total_offers'] ?? count($offers)); ?></strong><small>coupon attivi</small></div>
                <div class="stat"><strong><?php echo e($stats['total_stores'] ?? count($stores)); ?></strong><small>negozi partner</small></div>
                <div class="stat"><strong><?php echo e($stats['total_categories'] ?? count($categories)); ?></strong><small>categorie</small></div>
            </div>
        </div>
        <?php if ($heroOffer !== null): ?>
            <div class="hero-card" data-hero-offer-rotator data-hero-offers="<?php echo e(json_encode($heroSlides, JSON_UNESCAPED_UNICODE)); ?>">
                <div class="floating-badge">🔥 OFFERTA DEL GIORNO</div>
                <div class="mock-store">
                    <div class="store-logo"><?php echo e($heroStore['initial'] ?? '?'); ?></div>
                    <div><strong data-hero-store-name><?php echo e($heroStore['name'] ?? 'Store'); ?></strong><small data-hero-offer-type><?php echo e(\App\Helpers\OfferHelper::getOfferTypeLabel((string) $heroOffer['type'])); ?></small></div>
                </div>
                <div class="mock-offer">
                    <div class="discount" data-hero-discount <?php echo $heroDiscountLabel === '' ? 'hidden' : ''; ?>><?php echo e($heroDiscountLabel); ?></div>
                    <h3 data-hero-title><?php echo e($heroOffer['title']); ?></h3>
                    <p data-hero-description><?php echo e($heroOffer['description']); ?></p>
                </div>
                <button class="mock-button" type="button"
                    data-hero-code-button
                    data-offer-code="<?php echo e($heroOffer['code'] ?? ''); ?>"
                    data-offer-track="<?php echo e(url('/go/' . $heroOffer['id'])); ?>"
                    <?php echo empty($heroOffer['code']) ? 'hidden' : ''; ?>>
                    Mostra codice
                </button>
                <a class="mock-button" data-hero-direct-link href="<?php echo e(url('/go/' . $heroOffer['id'])); ?>"
                    rel="nofollow sponsored noopener noreferrer" target="_blank"
                    <?php echo ! empty($heroOffer['code']) ? 'hidden' : ''; ?>>
                    Vai all'offerta
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>
