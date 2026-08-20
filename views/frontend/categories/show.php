<?php echo app('view')->partial('layouts/partials/breadcrumbs', ['breadcrumbs' => $breadcrumbs]); ?>
<section class="page-intro"><div class="container"><h1><?php echo e($category['name']); ?></h1><p><?php echo e($category['description']); ?></p></div></section>
<section><div class="container"><?php echo app('view')->partial('frontend/partials/coupon-grid', ['offers' => $offers]); ?></div></section>
