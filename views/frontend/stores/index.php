<?php echo app('view')->partial('layouts/partials/breadcrumbs', ['breadcrumbs' => [['label' => 'Negozi', 'url' => '/negozi']]]); ?>
<section class="page-intro"><div class="container"><h1>Negozi partner</h1><p>Consulta i brand disponibili e accedi rapidamente alle relative offerte.</p></div></section>
<section><div class="container"><?php echo app('view')->partial('frontend/partials/store-grid', ['stores' => $stores]); ?></div></section>
