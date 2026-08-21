<?php $f = $dashboard['filters'] ?? ['preset' => '30d', 'start' => date('Y-m-d'), 'end' => date('Y-m-d')]; ?>
<div class="stack">
    <div class="hero-actions">
        <div>
            <h1>Analytics</h1>
            <p class="muted">Report giornalieri, dettagli click e filtri avanzati.</p>
        </div>
        <a class="btn" href="<?php echo e(url('/admin/analytics/export?preset=' . ($f['preset'] ?? '30d') . '&start_date=' . ($f['start'] ?? '') . '&end_date=' . ($f['end'] ?? ''))); ?>">Esporta CSV</a>
    </div>

    <form class="panel form-grid" method="get" action="<?php echo e(url('/admin/analytics')); ?>">
        <div class="form-group">
            <label>Preset</label>
            <select class="form-select" name="preset">
                <option value="today" <?php echo ($f['preset'] ?? '') === 'today' ? 'selected' : ''; ?>>Oggi</option>
                <option value="yesterday" <?php echo ($f['preset'] ?? '') === 'yesterday' ? 'selected' : ''; ?>>Ieri</option>
                <option value="7d" <?php echo ($f['preset'] ?? '') === '7d' ? 'selected' : ''; ?>>Ultimi 7 giorni</option>
                <option value="30d" <?php echo ($f['preset'] ?? '') === '30d' ? 'selected' : ''; ?>>Ultimi 30 giorni</option>
                <option value="custom" <?php echo ($f['preset'] ?? '') === 'custom' ? 'selected' : ''; ?>>Personalizzato</option>
            </select>
        </div>
        <div class="form-group">
            <label>Data inizio</label>
            <input class="form-control" type="date" name="start_date" value="<?php echo e($f['start'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>Data fine</label>
            <input class="form-control" type="date" name="end_date" value="<?php echo e($f['end'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>&nbsp;</label>
            <button class="btn" type="submit">Applica filtri</button>
        </div>
    </form>

    <?php echo app('view')->partial('admin/partials/kpis', ['dashboard' => $dashboard]); ?>

    <section class="panel">
        <h2>Andamento giornaliero</h2>
        <canvas id="analyticsDailyChart" height="120"></canvas>
    </section>

    <div class="card-grid">
        <section class="panel">
            <h2>Top offerte</h2>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Offerta</th><th>Negozio</th><th>Click</th></tr></thead>
                    <tbody>
                        <?php foreach ($dashboard['top_offers'] as $offer): ?>
                            <tr>
                                <td><?php echo e($offer['title'] ?? $offer['offer_title'] ?? '-'); ?></td>
                                <td><?php echo e($offer['store_name'] ?? '-'); ?></td>
                                <td><?php echo e((string) ($offer['clicks'] ?? $offer['click_count'] ?? 0)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <section class="panel">
            <h2>Top negozi</h2>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Negozio</th><th>Click</th><th>Offerte</th></tr></thead>
                    <tbody>
                        <?php foreach ($dashboard['top_stores'] as $store): ?>
                            <tr>
                                <td><?php echo e($store['name'] ?? '-'); ?></td>
                                <td><?php echo e((string) ($store['clicks'] ?? $store['click_count'] ?? 0)); ?></td>
                                <td><?php echo e((string) ($store['offers_count'] ?? 0)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <section class="panel">
        <h2>Dettaglio click recenti</h2>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Data</th><th>Offerta</th><th>Negozio</th><th>Sessione</th><th>Referer</th></tr></thead>
                <tbody>
                    <?php foreach (($dashboard['recent_clicks'] ?? []) as $row): ?>
                        <tr>
                            <td><?php echo e($row['created_at'] ?? '-'); ?></td>
                            <td><?php echo e($row['offer_title'] ?? '-'); ?></td>
                            <td><?php echo e($row['store_name'] ?? '-'); ?></td>
                            <td><small><?php echo e($row['session_id'] ?? '-'); ?></small></td>
                            <td><small><?php echo e($row['referer'] ?? '-'); ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<script>
(function () {
    var series = <?php echo json_encode($dashboard['series'] ?? [], JSON_UNESCAPED_UNICODE); ?>;
    var canvas = document.getElementById('analyticsDailyChart');
    if (!canvas || !series.length || !canvas.getContext) return;
    var ctx = canvas.getContext('2d');
    var width = canvas.width = canvas.clientWidth || 800;
    var height = canvas.height = 220;
    var padding = 28;
    var maxY = 1;
    series.forEach(function (row) {
        var c = Number(row.clicks || 0);
        var v = Number(row.page_views || 0);
        maxY = Math.max(maxY, c, v);
    });
    function y(val) { return height - padding - ((val / maxY) * (height - (padding * 2))); }
    function x(i) { return padding + ((width - (padding * 2)) * (series.length <= 1 ? 0 : i / (series.length - 1))); }
    ctx.clearRect(0, 0, width, height);
    ctx.strokeStyle = '#e8e6f1';
    ctx.beginPath();
    ctx.moveTo(padding, height - padding);
    ctx.lineTo(width - padding, height - padding);
    ctx.stroke();
    function drawLine(key, color) {
        ctx.strokeStyle = color;
        ctx.lineWidth = 2;
        ctx.beginPath();
        series.forEach(function (row, i) {
            var px = x(i);
            var py = y(Number(row[key] || 0));
            if (i === 0) ctx.moveTo(px, py); else ctx.lineTo(px, py);
        });
        ctx.stroke();
    }
    drawLine('page_views', '#8b8ca7');
    drawLine('clicks', '#6542ec');
})();
</script>
