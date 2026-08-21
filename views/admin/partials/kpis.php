<div class="stats-grid">
    <article class="metric-card"><div class="mini-logo">%</div><div><small>Coupon</small><strong><?php echo e((string) $dashboard['kpis']['offers']); ?></strong></div></article>
    <article class="metric-card"><div class="mini-logo">S</div><div><small>Negozi</small><strong><?php echo e((string) $dashboard['kpis']['stores']); ?></strong></div></article>
    <article class="metric-card"><div class="mini-logo">👁</div><div><small>Visite totali</small><strong><?php echo e((string) ($dashboard['kpis']['total_page_views'] ?? 0)); ?></strong></div></article>
    <article class="metric-card"><div class="mini-logo">C</div><div><small>Click totali</small><strong><?php echo e((string) ($dashboard['kpis']['total_clicks'] ?? 0)); ?></strong></div></article>
    <article class="metric-card"><div class="mini-logo">30</div><div><small>Click 30gg</small><strong><?php echo e((string) $dashboard['kpis']['clicks_30d']); ?></strong></div></article>
    <article class="metric-card"><div class="mini-logo">↗</div><div><small>Conversione</small><strong><?php echo e((string) $dashboard['kpis']['conversion_rate']); ?></strong></div></article>
</div>
