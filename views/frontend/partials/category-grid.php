<?php if (! empty($categories)): ?>
<div class="category-grid"><?php foreach ($categories as $category): ?><a class="category" href="<?php echo e(url('/categoria/' . $category['slug'])); ?>"><div class="category-icon"><?php echo e($category['icon']); ?></div><strong><?php echo e($category['name']); ?></strong><small><?php echo (int) ($category['offer_count'] ?? 0); ?> offerte</small></a><?php endforeach; ?></div>
<?php else: ?>
<div class="empty-state">Nessuna categoria disponibile</div>
<?php endif; ?>
