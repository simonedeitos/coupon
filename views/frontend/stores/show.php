<?php echo app('view')->partial('layouts/partials/breadcrumbs', ['breadcrumbs' => $breadcrumbs]); ?>
<section class="page-intro"><div class="container"><h1><?php echo e($store['name']); ?></h1><p><?php echo e($store['description']); ?></p><div class="hero-actions"><a class="btn btn-secondary" href="<?php echo e($store['website']); ?>" target="_blank" rel="noopener">Sito ufficiale</a></div></div></section>
<section><div class="container"><?php echo app('view')->partial('frontend/partials/coupon-grid', ['offers' => $offers]); ?></div></section>
