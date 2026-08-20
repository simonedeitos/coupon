<!doctype html>
<html lang="it">
<head>
    <?php echo app('view')->partial('layouts/partials/head', ['meta' => $meta ?? app('seo')->meta(['path' => request_path()])]); ?>
    <link rel="stylesheet" href="<?php echo e(css('style.css')); ?>">
    <script defer src="<?php echo e(js('main.js')); ?>"></script>
</head>
<body>
<?php echo app('view')->partial('layouts/partials/header'); ?>
<?php if ($message = flash('success')): ?><div class="container flash flash-success"><?php echo e($message); ?></div><?php endif; ?>
<?php if ($message = flash('error')): ?><div class="container flash flash-error"><?php echo e($message); ?></div><?php endif; ?>
<?php echo $content; ?>
<?php echo app('view')->partial('layouts/partials/footer'); ?>
<div class="modal" data-coupon-modal hidden>
    <div class="modal-card"><div class="pill">Coupon pronto all'uso</div><h3>Copia il tuo codice</h3><span class="modal-code" data-coupon-code></span><p class="muted">Il codice è stato copiato negli appunti quando disponibile.</p><button class="btn" type="button" data-close-modal>Chiudi</button></div>
</div>
</body>
</html>
