# Couponami

Piattaforma coupon SEO-friendly in PHP 8.3 senza framework. Include frontend pubblico, area admin, API, import affiliate e job cron.

## Struttura

- `index.php` front controller (root pubblica)
- `.htaccess` rewrite + hardening per deployment in `public_html`
- `assets/` CSS/JS/immagini pubblici
- `app/` logica applicativa (controller, servizi, repository, middleware)
- `views/` template frontend e admin
- `routes/` definizione rotte web/admin/api
- `config/` configurazioni app, db, seo, affiliate
- `cron/` script periodici (sitemap, cleanup, sync)
- `storage/` cache/log/sitemap generati

## Avvio locale rapido

```bash
php -S 127.0.0.1:8000 index.php
```

## Requisiti

- PHP 8.3+
- MySQL 8+ (opzionale: fallback seed in cache locale)
- estensioni: `pdo_mysql`, `json`, `mbstring`

## Sicurezza

- credenziali tramite variabili ambiente (`DB_*`, `APP_URL`, ecc.)
- protezione CSRF e middleware auth/role
- hardening .htaccess su directory sensibili
