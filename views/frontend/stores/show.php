<?php echo app('view')->partial('layouts/partials/breadcrumbs', ['breadcrumbs' => $breadcrumbs]); ?>

<?php $websiteUrl = (string) ($store['website_url'] ?? ''); ?>
<section class="page-intro">
    <div class="container">
        <h1><?php echo e($store['name']); ?></h1>
        <p><?php echo e($store['description']); ?></p>
        <?php if ($websiteUrl !== ''): ?>
            <div class="hero-actions">
                <a class="btn btn-secondary" href="<?php echo e($websiteUrl); ?>" target="_blank" rel="noopener noreferrer">
                    Visita sito
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<section>
    <div class="container">
        <?php echo app('view')->partial('frontend/partials/coupon-grid', ['offers' => $offers]); ?>
    </div>
</section>
