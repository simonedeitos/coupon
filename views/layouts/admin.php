<!doctype html>
<html lang="it">
<head>
    <?php echo app('view')->partial('layouts/partials/head', ['meta' => $meta ?? app('seo')->meta(['path' => request_path()])]); ?>
    <link rel="stylesheet" href="<?php echo e(asset('/assets/css/style.css')); ?>">
    <script defer src="<?php echo e(asset('/assets/js/main.js')); ?>"></script>
</head>
<body>
<?php if (request_path() === '/admin'): ?>
    <?php if ($message = flash('success')): ?><div class="container flash flash-success"><?php echo e($message); ?></div><?php endif; ?>
    <?php if ($message = flash('error')): ?><div class="container flash flash-error"><?php echo e($message); ?></div><?php endif; ?>
    <?php echo $content; ?>
<?php else: ?>
    <div class="admin-shell"><?php echo app('view')->partial('admin/partials/nav'); ?><main class="admin-main"><?php if ($message = flash('success')): ?><div class="flash flash-success"><?php echo e($message); ?></div><?php endif; ?><?php if ($message = flash('error')): ?><div class="flash flash-error"><?php echo e($message); ?></div><?php endif; ?><?php echo $content; ?></main></div>
<?php endif; ?>
</body>
</html>
