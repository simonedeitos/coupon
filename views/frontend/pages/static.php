<?php echo app('view')->partial('layouts/partials/breadcrumbs', ['breadcrumbs' => $breadcrumbs]); ?>
<section class="page-intro"><div class="container"><h1><?php echo e($page['title']); ?></h1><p><?php echo e($page['summary']); ?></p></div></section>
<section><div class="container card-grid"><?php foreach ($page['sections'] as $section): ?><article class="info-card"><h3><?php echo e($section['title']); ?></h3><p class="muted"><?php echo e($section['body']); ?></p></article><?php endforeach; ?></div></section>
