<div class="stack">
    <div>
        <h1><?php echo $item ? 'Modifica coupon' : 'Nuovo coupon'; ?></h1>
        <p class="muted">Gestisci titolo, stato, priorità, codice e dedupe esterno.</p>
    </div>
    <form class="panel form-grid" action="<?php echo e(url('/admin/offers/save')); ?>" method="post">
        <?php echo csrf_field(); ?>
        <?php if ($item): ?>
            <input type="hidden" name="id" value="<?php echo e((string) $item['id']); ?>">
        <?php endif; ?>

        <div class="form-group form-group-full">
            <label>Titolo</label>
            <input class="form-control" name="title" required value="<?php echo e($item['title'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label>Store</label>
            <select class="form-select" name="store_id">
                <?php foreach ($stores as $store): ?>
                    <option value="<?php echo e((string) $store['id']); ?>" <?php echo (int) ($item['store_id'] ?? 0) === (int) $store['id'] ? 'selected' : ''; ?>>
                        <?php echo e($store['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Categoria</label>
            <select class="form-select" name="category_id">
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo e((string) $category['id']); ?>" <?php echo (int) ($item['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : ''; ?>>
                        <?php echo e($category['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Tipo</label>
            <select class="form-select" name="type">
                <option value="CODICE" <?php echo ($item['type'] ?? 'CODICE') === 'CODICE' ? 'selected' : ''; ?>>CODICE</option>
                <option value="OFFERTA" <?php echo ($item['type'] ?? '') === 'OFFERTA' ? 'selected' : ''; ?>>OFFERTA</option>
            </select>
        </div>

        <div class="form-group">
            <label>Stato</label>
            <select class="form-select" name="status">
                <?php foreach ($statuses as $status): ?>
                    <option value="<?php echo e($status); ?>" <?php echo ($item['status'] ?? 'ACTIVE') === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Tipo sconto</label>
            <select class="form-select" name="discount_type">
                <option value="NONE" <?php echo (($item['discount_type'] ?? 'NONE') === 'NONE' || empty($item['discount_type'])) ? 'selected' : ''; ?>>Nessuno</option>
                <option value="PERCENT" <?php echo ($item['discount_type'] ?? '') === 'PERCENT' ? 'selected' : ''; ?>>Percentuale (%)</option>
                <option value="AMOUNT" <?php echo ($item['discount_type'] ?? '') === 'AMOUNT' ? 'selected' : ''; ?>>Importo fisso (€)</option>
            </select>
        </div>

        <div class="form-group">
            <label>Valore sconto</label>
            <input class="form-control" name="discount_value" type="number" min="0" step="0.01" value="<?php echo e((string) ($item['discount_value'] ?? $item['discount'] ?? '')); ?>">
        </div>

        <div class="form-group">
            <label>Codice</label>
            <input class="form-control" name="code" value="<?php echo e($item['code'] ?? ''); ?>">
        </div>

        <div class="form-group form-group-full">
            <label>Descrizione</label>
            <textarea class="form-textarea" rows="4" name="description"><?php echo e($item['description'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label>URL affiliato</label>
            <input class="form-control" name="affiliate_url" value="<?php echo e($item['affiliate_url'] ?? 'https://example.com'); ?>">
        </div>

        <div class="form-group">
            <label>External ID</label>
            <input class="form-control" name="external_id" value="<?php echo e($item['external_id'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label>Scadenza</label>
            <input class="form-control" name="expires_at" value="<?php echo e($item['expires_at'] ?? date('Y-m-d')); ?>">
        </div>

        <div class="form-group">
            <label>Priorità</label>
            <input class="form-control" name="priority" type="number" value="<?php echo e((string) ($item['priority'] ?? 50)); ?>">
        </div>

        <div class="form-group checkbox">
            <input type="hidden" name="featured" value="0">
            <input type="checkbox" name="featured" value="1" <?php echo ! empty($item['featured']) ? 'checked' : ''; ?>>
            <label>Featured (override manuale)</label>
        </div>

        <div class="form-group-full">
            <button class="btn" type="submit">Salva coupon</button>
        </div>
    </form>
</div>
