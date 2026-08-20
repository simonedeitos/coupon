<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo e($meta['title'] ?? config('seo.default_title')); ?></title>
<meta name="description" content="<?php echo e($meta['description'] ?? config('seo.default_description')); ?>">
<?php if (! empty($meta['keywords'])): ?><meta name="keywords" content="<?php echo e($meta['keywords']); ?>"><?php endif; ?>
<meta name="theme-color" content="#6d4aff">
<link rel="canonical" href="<?php echo e($meta['canonical'] ?? url(request_path())); ?>">
<meta property="og:type" content="<?php echo e($meta['type'] ?? 'website'); ?>">
<meta property="og:title" content="<?php echo e($meta['title'] ?? config('seo.default_title')); ?>">
<meta property="og:description" content="<?php echo e($meta['description'] ?? config('seo.default_description')); ?>">
<meta property="og:url" content="<?php echo e($meta['canonical'] ?? url(request_path())); ?>">
<meta property="og:site_name" content="<?php echo e(config('seo.site_name')); ?>">
<meta name="twitter:card" content="<?php echo e(config('seo.twitter_card')); ?>">
<script type="application/ld+json"><?php echo json_encode($meta['jsonLd'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
