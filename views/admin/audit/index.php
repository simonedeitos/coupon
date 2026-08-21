<div class="stack">
    <div>
        <h1>Audit log</h1>
        <p class="muted">Traccia azioni amministrative rilevanti.</p>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Azione</th>
                    <th>Attore</th>
                    <th>Target</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo e($item['action'] ?? '-'); ?></td>
                        <td><?php echo e($item['actor'] ?? '-'); ?></td>
                        <td><?php echo e((string) ($item['target'] ?? '-')); ?></td>
                        <td><?php echo e($item['created_at'] ?? '-'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (($pagination['last_page'] ?? 1) > 1): ?>
        <div class="hero-actions">
            <?php if (($pagination['page'] ?? 1) > 1): ?>
                <a class="btn btn-secondary" href="<?php echo e(url('/admin/audit?page=' . (($pagination['page'] ?? 1) - 1))); ?>">← Precedente</a>
            <?php else: ?>
                <span></span>
            <?php endif; ?>
            <?php if (($pagination['page'] ?? 1) < ($pagination['last_page'] ?? 1)): ?>
                <a class="btn btn-secondary" href="<?php echo e(url('/admin/audit?page=' . (($pagination['page'] ?? 1) + 1))); ?>">Successiva →</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
