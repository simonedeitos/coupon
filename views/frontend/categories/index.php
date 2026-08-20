<?php echo app('view')->partial('layouts/partials/breadcrumbs', ['breadcrumbs' => [['label' => 'Categorie', 'url' => '/categorie']]]); ?>
<section class="page-intro"><div class="container"><h1>Tutte le categorie</h1><p>Una vista completa dei verticali Couponami.</p></div></section>
<section><div class="container"><?php echo app('view')->partial('frontend/partials/category-grid', ['categories' => $categories]); ?></div></section>
