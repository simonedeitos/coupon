<div class="stack">
    <div>
        <h1>SEO & Ottimizzazioni</h1>
        <p class="muted">Panoramica completa metadati, contenuti indicizzabili e applicazioni SEO.</p>
    </div>

    <section class="panel">
        <h2>Configurazioni SEO operative</h2>
        <form class="form-grid" action="<?php echo e(url('/admin/settings/save')); ?>" method="post">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label>Nome sito</label>
                <input class="form-control" name="site_name" value="<?php echo e($settings['site_name'] ?? 'Couponami'); ?>">
            </div>
            <div class="form-group">
                <label>Email supporto</label>
                <input class="form-control" name="support_email" value="<?php echo e($settings['support_email'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Valuta default</label>
                <input class="form-control" name="default_currency" value="<?php echo e($settings['default_currency'] ?? 'EUR'); ?>">
            </div>
            <div class="form-group">
                <label>Max URL sitemap</label>
                <input class="form-control" name="sitemap_max_urls" value="<?php echo e($settings['sitemap_max_urls'] ?? '50000'); ?>">
            </div>
            <div class="form-group form-group-full">
                <button class="btn" type="submit">Salva impostazioni SEO</button>
            </div>
        </form>
    </section>

    <div class="card-grid">
        <section class="panel">
            <h2>Checklist tecnica SEO</h2>
            <ul class="help-list">
                <li>Title e meta description dinamici per home, categorie, negozi, offerte e ricerca.</li>
                <li>Breadcrumb con Schema.org per percorsi interni.</li>
                <li>Sitemap XML e robots.txt già esposti lato pubblico.</li>
                <li>JSON-LD base su pagine principali (WebSite/Article).</li>
                <li>Struttura URL pulita e slug SEO-friendly.</li>
            </ul>
        </section>
        <section class="panel">
            <h2>Applicazioni consigliate</h2>
            <ul class="help-list">
                <li>Prioritizzare offerte con più click per aumentare CTR organico.</li>
                <li>Aggiornare frequentemente descrizioni categoria e store.</li>
                <li>Usare dati analytics per topic stagionali e landing dedicate.</li>
                <li>Monitorare pagine senza click e migliorare title/description.</li>
                <li>Mantenere coerenza tra badge sconto e contenuto pagina.</li>
            </ul>
        </section>
    </div>

    <section class="panel">
        <h2>Anteprima metadati indicizzabili</h2>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Tipo</th><th>Pagina</th><th>Title</th><th>Description</th><th>Offerte</th></tr></thead>
                <tbody>
                    <?php foreach ($pages as $page): ?>
                        <tr>
                            <td><?php echo e($page['type'] ?? '-'); ?></td>
                            <td><?php echo e($page['name'] ?? '-'); ?></td>
                            <td><small><?php echo e($page['title'] ?? '-'); ?></small></td>
                            <td><small><?php echo e($page['description'] ?? '-'); ?></small></td>
                            <td><?php echo e((string) ($page['offers'] ?? '-')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
