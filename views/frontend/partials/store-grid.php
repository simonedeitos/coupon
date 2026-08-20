<?php if (! empty($stores)): ?>
<div class="store-grid"><?php foreach ($stores as $store): ?><a class="store-card" href="<?php echo e(url('/negozio/' . $store['slug'])); ?>"><div class="mini-logo"><?php echo e($store['initial']); ?></div><strong><?php echo e($store['name']); ?></strong><small><?php echo (int) ($store['offers_count'] ?? 0); ?> offerte</small></a><?php endforeach; ?></div>
<?php else: ?>
<div class="empty-state">Nessun negozio disponibile</div>
<?php endif; ?>
