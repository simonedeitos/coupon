<main>
    <!-- Barra di ricerca compatta + filtri rapidi (sopra tutto il contenuto) -->
    <div class="search-bar-wrap">
        <div class="container">
            <form class="search-compact" action="<?php echo e(url('/cerca')); ?>" method="get">
                <span class="search-icon">⌕</span>
                <input type="search" name="q" placeholder="Cerca coupon, negozio o categoria..." aria-label="Cerca coupon">
                <button type="submit">Cerca</button>
            </form>
            <!-- Filtri rapidi per categoria e tipo -->
            <div class="quick-filters">
                <a class="filter-chip <?php echo request_input('cat', '') === '' && request_input('tipo', '') === '' ? 'active' : ''; ?>"
                   href="<?php echo e(url('/coupon')); ?>">Tutti</a>
                <?php foreach (array_slice($categories, 0, 6) as $cat): ?>
                    <a class="filter-chip" href="<?php echo e(url('/categoria/' . $cat['slug'])); ?>">
                        <?php echo e($cat['icon'] ?? ''); ?> <?php echo e($cat['name']); ?>
                    </a>
                <?php endforeach; ?>
                <a class="filter-chip" href="<?php echo e(url('/coupon?tipo=codice')); ?>">Solo con codice</a>
                <a class="filter-chip" href="<?php echo e(url('/coupon?ordine=recenti')); ?>">Più recenti</a>
                <a class="filter-chip" href="<?php echo e(url('/coupon?ordine=popolari')); ?>">Più popolari</a>
            </div>
        </div>
    </div>

    <?php echo app('view')->partial('frontend/partials/hero', ['categories' => $categories, 'offers' => $offers, 'stores' => $stores, 'stats' => $stats ?? []]); ?>
    <section id="categorie"><div class="container"><div class="section-head"><div><h2>Esplora per categoria</h2><p>Trova rapidamente le offerte che ti interessano.</p></div><a class="view-all" href="<?php echo e(url('/categorie')); ?>">Vedi tutte →</a></div><?php echo app('view')->partial('frontend/partials/category-grid', ['categories' => $categories]); ?></div></section>
    <section id="coupon"><div class="container"><div class="section-head"><div><h2>Coupon in evidenza</h2><p>Le offerte più popolari del momento.</p></div><a class="view-all" href="<?php echo e(url('/coupon')); ?>">Vedi tutti →</a></div><?php echo app('view')->partial('frontend/partials/coupon-grid', ['offers' => $offers]); ?></div></section>
    <section class="stores-band" id="negozi"><div class="container"><div class="section-head"><div><h2>Negozi popolari</h2><p>Brand con il maggior numero di offerte attive.</p></div><a class="view-all" href="<?php echo e(url('/negozi')); ?>">Tutti i negozi →</a></div><?php echo app('view')->partial('frontend/partials/store-grid', ['stores' => $stores]); ?></div></section>
    <?php echo app('view')->partial('frontend/partials/deal-of-the-day', ['dealOfTheDay' => $dealOfTheDay ?? []]); ?>
    <section id="ultime-offerte"><div class="container"><div class="section-head"><div><h2>Ultime offerte</h2><p>Le promozioni aggiunte più recentemente.</p></div><a class="view-all" href="<?php echo e(url('/coupon?ordine=recenti')); ?>">Vedi tutte →</a></div><div class="latest-layout"><?php echo app('view')->partial('frontend/partials/latest-offers', ['offers' => $latest, 'storesById' => $storesById]); ?><?php echo app('view')->partial('frontend/partials/newsletter'); ?></div></div></section>
    <section class="cta"><div class="container"><div class="cta-box"><h2>Un buon affare è sempre una buona idea.</h2><p>Cerca tra categorie, negozi e coupon verificati.</p><a class="cta-button" href="<?php echo e(url('/coupon')); ?>">Scopri i coupon</a></div></div></section>
</main>
