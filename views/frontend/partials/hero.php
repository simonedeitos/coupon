<section class="hero">
    <div class="container hero-inner">
        <div>
            <div class="eyebrow">✦ Risparmia ad ogni acquisto</div>
            <h1>Trova il tuo prossimo <em>affare.</em></h1>
            <p>Codici sconto, coupon e offerte dei tuoi negozi preferiti. Cerca, scegli e risparmia in pochi secondi.</p>
            <form class="search search-compact" action="<?php echo e(url('/cerca')); ?>" method="get">
                <span class="search-icon">⌕</span>
                <input type="search" name="q" placeholder="Cerca coupon, negozio o categoria..." aria-label="Cerca coupon">
                <button type="submit">Cerca</button>
            </form>
            <div class="category-filters">
                <?php foreach (array_slice($categories, 0, 8) as $category): ?>
                    <a class="pill" href="<?php echo e(url('/categoria/' . $category['slug'])); ?>"><?php echo e($category['icon']); ?> <?php echo e($category['name']); ?></a>
                <?php endforeach; ?>
            </div>
            <div class="hero-stats">
                <div class="stat"><strong><?php echo e($stats['total_offers'] ?? count($offers)); ?></strong><small>coupon attivi</small></div>
                <div class="stat"><strong><?php echo e($stats['total_stores'] ?? count($stores)); ?></strong><small>negozi partner</small></div>
                <div class="stat"><strong><?php echo e($stats['total_categories'] ?? count($categories)); ?></strong><small>categorie</small></div>
            </div>
        </div>
        <div class="hero-card">
            <div class="floating-badge">🔥 OFFERTA DEL GIORNO</div>
            <?php if (! empty($offers[0])): ?>
                <?php $featuredOffer = $offers[0]; $featuredStore = app('storeRepository')->findById((int) $featuredOffer['store_id']); ?>
                <?php $discountLabel = \App\Helpers\OfferHelper::formatDiscount($featuredOffer); ?>
                <div class="mock-store">
                    <div class="store-logo"><?php echo e($featuredStore['initial'] ?? '?'); ?></div>
                    <div><strong><?php echo e($featuredStore['name'] ?? 'Store'); ?></strong><small><?php echo e($featuredOffer['type']); ?></small></div>
                </div>
                <div class="mock-offer">
                    <?php if ($discountLabel !== ''): ?>
                        <div class="discount"><?php echo e($discountLabel); ?></div>
                    <?php endif; ?>
                    <h3><?php echo e($featuredOffer['title']); ?></h3>
                    <p><?php echo e($featuredOffer['description']); ?></p>
                </div>
                <?php if (! empty($featuredOffer['code'])): ?>
                    <button class="mock-button" type="button"
                        data-offer-code="<?php echo e($featuredOffer['code']); ?>"
                        data-offer-track="<?php echo e(url('/go/' . $featuredOffer['id'])); ?>">
                        Mostra codice
                    </button>
                <?php else: ?>
                    <a class="mock-button" href="<?php echo e(url('/go/' . $featuredOffer['id'])); ?>" rel="nofollow sponsored noopener" target="_blank">Vai all'offerta</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
