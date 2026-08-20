<?php if (! empty($breadcrumbs ?? [])): ?>
    <div class="container page-intro"><ol class="breadcrumb"><li><a href="<?php echo e(url('/')); ?>">Home</a></li><?php foreach (($breadcrumbs ?? []) as $item): ?><li><a href="<?php echo e(url($item['url'])); ?>"><?php echo e($item['label']); ?></a></li><?php endforeach; ?></ol></div>
<?php endif; ?>
