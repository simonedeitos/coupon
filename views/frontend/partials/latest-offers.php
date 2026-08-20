<?php $storeIndex = $storesById ?? array_column(app('storeRepository')->all(), null, 'id'); ?>
<?php if (! empty($offers)): ?>
<div class="latest-list">
    <?php foreach ($offers as $offer): ?>
        <?php $store = $storeIndex[(int) $offer['store_id']] ?? null; ?>
        <?php $discount = \App\Helpers\OfferHelper::formatDiscount($offer); ?>
        <?php $hasCode = ! empty($offer['code']); ?>
        <div class="latest-item">
            <div class="mini-logo"><?php echo e($store['initial'] ?? '?'); ?></div>
            <div class="latest-info">
                <strong><?php echo e($store['name'] ?? 'Store'); ?> | <?php echo e($offer['title']); ?></strong><br>
                <small><?php echo e(\App\Helpers\OfferHelper::getOfferTypeLabel((string) $offer['type'])); ?></small>
            </div>
            <div class="latest-actions">
                <?php if ($discount !== ''): ?>
                    <span class="badge-discount"><?php echo e($discount); ?></span>
                <?php endif; ?>
                <?php if ($hasCode): ?>
                    <button class="btn btn-sm redeem"
                            type="button"
                            data-offer-code="<?php echo e($offer['code']); ?>"
                            data-offer-track="<?php echo e(url('/go/' . $offer['id'])); ?>">
                        Mostra codice
                    </button>
                <?php else: ?>
                    <a class="btn btn-sm"
                       href="<?php echo e(url('/go/' . $offer['id'])); ?>"
                       target="_blank"
                       rel="nofollow sponsored noopener noreferrer">
                        Vai all'offerta
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="empty-state">Nessuna offerta recente</div>
<?php endif; ?>