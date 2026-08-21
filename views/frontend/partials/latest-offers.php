<?php $storeIndex = $storesById ?? array_column(app('storeRepository')->all(), null, 'id'); ?>
<?php if (! empty($offers)): ?>
<div class="latest-list latest-cards">
    <?php foreach ($offers as $offer): ?>
        <?php $store = $storeIndex[(int) $offer['store_id']] ?? null; ?>
        <?php $discountLabel = \App\Helpers\OfferHelper::formatDiscount($offer); ?>
        <?php $isAffiliateOnly = empty($offer['code']) && $discountLabel === ''; ?>
        <article class="latest-item latest-card">
            <div class="latest-store">
                <div class="mini-logo"><?php echo e($store['initial'] ?? '?'); ?></div>
                <div class="latest-store-meta">
                    <span class="latest-store-name"><?php echo e($store['name'] ?? 'Store'); ?></span>
                    <small><?php echo e(\App\Helpers\OfferHelper::getOfferTypeLabel((string) $offer['type'])); ?></small>
                </div>
            </div>
            <div class="latest-info">
                <h3 class="latest-title">
                    <a href="<?php echo e(url('/coupon/' . $offer['slug'])); ?>"><?php echo e($offer['title']); ?></a>
                </h3>
            </div>
            <div class="latest-actions">
                <?php if ($discountLabel !== ''): ?>
                    <span class="badge-discount"><?php echo e($discountLabel); ?></span>
                <?php endif; ?>
                <?php if ($isAffiliateOnly): ?>
                    <a class="btn-small" href="<?php echo e(url('/go/' . $offer['id'])); ?>"
                        target="_blank" rel="nofollow sponsored noopener noreferrer">
                        Vai al sito
                    </a>
                <?php elseif (! empty($offer['code'])): ?>
                    <button class="btn-small" type="button"
                        data-offer-code="<?php echo e($offer['code']); ?>"
                        data-offer-track="<?php echo e(url('/go/' . $offer['id'])); ?>">
                        Mostra codice
                    </button>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="empty-state">Nessuna offerta recente</div>
<?php endif; ?>
