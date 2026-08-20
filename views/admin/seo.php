<div class="stack">
<div>
    <h1>SEO Dashboard</h1>
    <p class="muted">Titoli e meta description generati dinamicamente con mese/anno per tutte le pagine principali.</p>
</div>
<section class="panel">
    <h2>Anteprima SERP Google</h2>
    <p class="muted">Visualizzazione titoli e meta description come appaiono nei risultati di ricerca. Titolo ottimale: 30–70 caratteri. Meta: 100–160 caratteri.</p>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Pagina</th>
                    <th>Titolo (len)</th>
                    <th>Meta Description (len)</th>
                    <th>Stato</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pages as $page): ?>
                <?php
                    $titleOk = $page['title_len'] >= 30 && $page['title_len'] <= 70;
                    $descOk = $page['desc_len'] >= 100 && $page['desc_len'] <= 160;
                    $statusClass = ($titleOk && $descOk) ? 'status-active' : 'status-scheduled';
                    $statusLabel = ($titleOk && $descOk) ? 'OK' : 'Attenzione';
                ?>
                <tr>
                    <td><span class="tag"><?php echo e($page['type']); ?></span></td>
                    <td><a href="<?php echo e($page['url']); ?>" target="_blank"><?php echo e($page['name']); ?></a></td>
                    <td>
                        <div class="serp-title"><?php echo e($page['title']); ?></div>
                        <small class="muted"><?php echo e((string) $page['title_len']); ?> caratteri<?php if (! ($page['title_len'] >= 30 && $page['title_len'] <= 70)): ?> ⚠️<?php endif; ?></small>
                    </td>
                    <td>
                        <div class="serp-desc"><?php echo e(mb_substr($page['description'], 0, 120)); ?><?php if (mb_strlen($page['description']) > 120): ?>…<?php endif; ?></div>
                        <small class="muted"><?php echo e((string) $page['desc_len']); ?> caratteri<?php if (! ($page['desc_len'] >= 100 && $page['desc_len'] <= 160)): ?> ⚠️<?php endif; ?></small>
                    </td>
                    <td><span class="tag <?php echo e($statusClass); ?>"><?php echo e($statusLabel); ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<section class="panel">
    <h2>Cron Jobs SEO</h2>
    <p class="muted">Script di aggiornamento automatico titoli SEO mese/anno.</p>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Job</th><th>Frequenza</th><th>Descrizione</th></tr></thead>
            <tbody>
                <tr><td><code>update-seo-cache.php</code></td><td>Ogni giorno (00:00)</td><td>Ricalcola cache titoli home, categorie, negozi</td></tr>
                <tr><td><code>refresh-all-seo-titles.php</code></td><td>1° del mese (00:00)</td><td>Refresh mensile completo quando cambia il mese</td></tr>
                <tr><td><code>validate-seo-metadata.php</code></td><td>Ogni settimana (domenica)</td><td>Valida lunghezza titoli e meta description</td></tr>
            </tbody>
        </table>
    </div>
    <pre class="muted" style="margin-top:1rem;font-size:.8rem"># Crontab
0 0 * * * php /var/www/couponami/cron/update-seo-cache.php
0 0 1 * * php /var/www/couponami/cron/refresh-all-seo-titles.php
0 0 * * 0 php /var/www/couponami/cron/validate-seo-metadata.php</pre>
</section>
</div>
