<?php $storeIndex = $storesById ?? array_column(app('storeRepository')->all(), null, 'id'); ?>
<?php if (! empty($offers)): ?>
<div class="latest-list">
    <?php foreach ($offers as $offer): ?>
        <?php $store = $storeIndex[(int) $offer['store_id']] ?? null; ?>
        <a class="latest-item" href="<?php echo e(url('/coupon/' . $offer['slug'])); ?>">
            <div class="mini-logo"><?php echo e($store['initial'] ?? '?'); ?></div>
            <div class="latest-info">
                <strong><?php echo e($offer['title']); ?></strong><br>
                <small><?php echo e($store['name'] ?? 'Store'); ?> · <?php echo e(\App\Helpers\OfferHelper::getOfferTypeLabel((string) $offer['type'])); ?></small>
            </div>
            <div class="latest-discount"><?php echo e(\App\Helpers\OfferHelper::formatDiscount($offer)); ?></div>
        </a>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="empty-state">Nessuna offerta recente</div>
<?php endif; ?>