<?php
/**
 * Helper locali per normalizzare i campi TradeDoubler, che possono variare nome
 * a seconda del programma/sistema (VOUCHERS vs PRODUCTS).
 */
function td_field(array $item, array $keys, $default = '—') {
    foreach ($keys as $key) {
        if (isset($item[$key]) && $item[$key] !== '' && $item[$key] !== null) {
            return $item[$key];
        }
    }
    return $default;
}

function td_date(array $item, array $keys): string {
    $raw = td_field($item, $keys, null);
    if ($raw === null) {
        return '—';
    }
    // Timestamp epoch in millisecondi o secondi
    if (is_numeric($raw)) {
        $raw = (int) $raw;
        $seconds = $raw > 9999999999 ? intdiv($raw, 1000) : $raw;
        return date('d/m/Y', $seconds);
    }
    $ts = strtotime((string) $raw);
    return $ts !== false ? date('d/m/Y', $ts) : (string) $raw;
}

/**
 * Formatta lo sconto TradeDoubler in modo leggibile.
 * TradeDoubler restituisce spesso il valore come frazione decimale (0.1 = 10%).
 */
function td_discount(array $item): string {
    $raw = td_field($item, ['discount', 'discountAmount', 'value', 'discountValue', 'discountText'], null);
    if ($raw === null) {
        return '—';
    }
    if (is_numeric($raw)) {
        $num = (float) $raw;
        if ($num > 0 && $num <= 1) {
            $percent = round($num * 100, 2);
            return rtrim(rtrim((string) $percent, '0'), '.') . '%';
        }
        return (string) $raw . '%';
    }
    return (string) $raw;
}
?>
<section class="page-intro">
    <div class="container">
        <div class="panel">
            <div class="pill">Affiliate</div>
            <h1>Importa coupon da TradeDoubler</h1>
            <p class="muted">
                Cerca voucher o prodotti disponibili su TradeDoubler per il sito selezionato, e importali direttamente nel database.
                Gli elementi già importati sono contrassegnati e non possono essere selezionati di nuovo. La parola chiave è opzionale.
            </p>

            <?php if ($error): ?>
                <div class="flash flash-error"><?php echo e($error); ?></div>
            <?php endif; ?>

            <form method="get" action="<?php echo e(url('/admin/tradedoubler')); ?>" class="stack" style="margin-bottom:24px;">
                <div class="form-group">
                    <label for="site">Sito TradeDoubler</label>
                    <select class="form-control" id="site" name="site">
                        <?php foreach ($sites as $key => $site): ?>
                            <option value="<?php echo e($key); ?>" <?php echo $key === $siteKey ? 'selected' : ''; ?>>
                                <?php echo e($site['label']); ?> (websiteId <?php echo e($site['website_id']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="system">Tipo</label>
                    <select class="form-control" id="system" name="system">
                        <option value="VOUCHERS" <?php echo $system === 'VOUCHERS' ? 'selected' : ''; ?>>Voucher / Coupon</option>
                        <option value="PRODUCTS" <?php echo $system === 'PRODUCTS' ? 'selected' : ''; ?>>Prodotti</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="keywords">Parola chiave / negozio (opzionale)</label>
                    <input class="form-control" id="keywords" name="keywords" value="<?php echo e($keywords); ?>" placeholder="es. asus, moda, tech... (lascia vuoto per vedere tutto)">
                </div>
                <button class="btn" type="submit">Cerca su TradeDoubler</button>
            </form>

            <?php if (empty($results) && !$error): ?>
                <div class="empty-state">
                    Nessun elemento trovato<?php echo $keywords !== '' ? ' per "' . e($keywords) . '"' : ''; ?> per il sito e sistema selezionati.
                </div>
            <?php endif; ?>

            <?php if (!empty($results)): ?>
                <form method="post" action="<?php echo e(url('/admin/tradedoubler/import')); ?>">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="system" value="<?php echo e($system); ?>">

                    <label style="display:inline-flex;align-items:center;gap:6px;margin-bottom:16px;">
                        <input type="checkbox" name="featured" value="1"> Metti in evidenza in home
                    </label>

                    <table class="table">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Stato</th>
                                <th>Negozio</th>
                                <th>Titolo</th>
                                <?php if ($system === 'VOUCHERS'): ?>
                                    <th>Codice</th>
                                    <th>Sconto</th>
                                    <th>Valido da</th>
                                    <th>Scadenza</th>
                                    <th>Tracking link</th>
                                <?php else: ?>
                                    <th>Prezzo</th>
                                    <th>Categoria</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $i => $item):
                                $alreadyImported = ! empty($item['_already_imported']);
                            ?>
                            <tr<?php echo $alreadyImported ? ' style="opacity:0.6;"' : ''; ?>>
                                <td>
                                    <input type="checkbox" name="selected[]" value="<?php echo (int) $i; ?>" <?php echo $alreadyImported ? 'disabled' : ''; ?>>
                                    <input type="hidden" name="payload[<?php echo (int) $i; ?>]" value="<?php echo e(json_encode($item, JSON_UNESCAPED_UNICODE)); ?>">
                                </td>
                                <td>
                                    <?php if ($alreadyImported): ?>
                                        <span class="badge" style="background:#1fa855;color:#fff;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:700;white-space:nowrap;">✓ Già importato</span>
                                    <?php else: ?>
                                        <span class="badge" style="background:#eee;color:#555;padding:3px 10px;border-radius:999px;font-size:12px;white-space:nowrap;">Nuovo</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e((string) td_field($item, ['programName', 'program', 'advertiserName'])); ?></td>
                                <td><?php echo e((string) td_field($item, ['title', 'name'])); ?></td>
                                <?php if ($system === 'VOUCHERS'): ?>
                                    <td><?php echo e((string) td_field($item, ['code', 'voucherCode'])); ?></td>
                                    <td><?php echo e(td_discount($item)); ?></td>
                                    <td><?php echo e(td_date($item, ['startDate', 'validFrom', 'start'])); ?></td>
                                    <td><?php echo e(td_date($item, ['endDate', 'validTo', 'end'])); ?></td>
                                    <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                        <?php echo e((string) td_field($item, ['trackingUrl', 'clickUrl', 'url', 'deepLink', 'link'])); ?>
                                    </td>
                                <?php else: ?>
                                    <td><?php echo e((string) td_field($item, ['price'])); ?></td>
                                    <td><?php echo e((string) td_field($item, ['categoryName', 'category'])); ?></td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <button class="btn" type="submit">Importa selezionati nel database</button>

                    <?php if (count($results) >= 50): ?>
                        <a class="btn btn-secondary" href="<?php echo e(url('/admin/tradedoubler?site=' . urlencode($siteKey) . '&system=' . urlencode($system) . '&keywords=' . urlencode($keywords) . '&page=' . ($page + 1))); ?>" style="margin-left:10px;">Pagina successiva →</a>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>