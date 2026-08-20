<?php echo app('view')->partial('layouts/partials/breadcrumbs', ['breadcrumbs' => $breadcrumbs]); ?>
<section class="page-intro"><div class="container"><h1>Contatti</h1><p>Hai una domanda, un suggerimento o una segnalazione? Scrivici e ti risponderemo al più presto.</p></div></section>
<section><div class="container"><div class="content-grid">
<div class="panel">
<form method="post" action="<?php echo e(url('/contatti')); ?>">
    <?php echo csrf_field(); ?>
    <div class="form-group">
        <label for="contact-name">Nome</label>
        <input id="contact-name" type="text" name="name" class="form-control" value="<?php echo e(old('name')); ?>" required maxlength="100" placeholder="Il tuo nome">
    </div>
    <div class="form-group" style="margin-top:14px">
        <label for="contact-email">Email</label>
        <input id="contact-email" type="email" name="email" class="form-control" value="<?php echo e(old('email')); ?>" required maxlength="190" placeholder="tua@email.it">
    </div>
    <div class="form-group" style="margin-top:14px">
        <label for="contact-subject">Oggetto</label>
        <select id="contact-subject" name="subject" class="form-control">
            <option value="info"<?php echo old('subject','info') === 'info' ? ' selected' : ''; ?>>Informazioni generali</option>
            <option value="segnalazione"<?php echo old('subject') === 'segnalazione' ? ' selected' : ''; ?>>Segnala un coupon scaduto</option>
            <option value="partnership"<?php echo old('subject') === 'partnership' ? ' selected' : ''; ?>>Partnership / Affiliazione</option>
            <option value="altro"<?php echo old('subject') === 'altro' ? ' selected' : ''; ?>>Altro</option>
        </select>
    </div>
    <div class="form-group" style="margin-top:14px">
        <label for="contact-message">Messaggio</label>
        <textarea id="contact-message" name="message" class="form-control" rows="6" required maxlength="2000" placeholder="Scrivi qui il tuo messaggio..."><?php echo e(old('message')); ?></textarea>
    </div>
    <div style="margin-top:18px"><button class="btn" type="submit">Invia messaggio</button></div>
</form>
</div>
<div class="stack">
    <div class="info-card">
        <h3>Email</h3>
        <p class="muted"><?php echo e(config('app.contact_email', 'info@couponami.it')); ?></p>
    </div>
    <div class="info-card">
        <h3>Tempi di risposta</h3>
        <p class="muted">Solitamente rispondiamo entro 1-2 giorni lavorativi.</p>
    </div>
    <div class="info-card">
        <h3>Segnalazioni coupon</h3>
        <p class="muted">Per coupon scaduti o non funzionanti usa il modulo selezionando "Segnala un coupon scaduto".</p>
    </div>
</div>
</div></div></section>

