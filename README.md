# Couponami

Couponami è una piattaforma coupon in PHP server-rendered con frontend pubblico, backend admin, schema MySQL, tracking click, cron di manutenzione e scaffolding TradeDoubler.

## Struttura inclusa

- `config/` configurazione app, database, affiliate e SEO
- `public/` front controller, asset, `.htaccess`, `robots.txt`
- `app/` controller, model, service, repository, middleware e integrazione TradeDoubler
- `views/` template frontend e admin
- `routes/` routing web, admin e API
- `cron/` sync affiliate, scadenza coupon, sitemap e cleanup
- `storage/` cache, log e sitemap generate
- `database/schema.sql` schema MySQL con 30 tabelle, indici e relazioni

## Avvio locale

```bash
php -S 127.0.0.1:8000 -t public public/index.php
```

Apri poi `http://127.0.0.1:8000`.

## Configurazione ambiente

La configurazione legge da variabili d'ambiente per evitare di salvare segreti nel repository:

- `APP_URL`
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `TRADEDOUBLER_API_KEY`, `TRADEDOUBLER_PUBLISHER_ID`, `TRADEDOUBLER_API_BASE`

Le credenziali admin richieste sono già supportate tramite hash Argon2id nel file `config/app.php`.

## Schema database

Importa `database/schema.sql` su MySQL/MariaDB:

```bash
mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" < database/schema.sql
```

## Cron consigliati

```bash
*/15 * * * * php /percorso/progetto/cron/sync-affiliate.php
0 * * * * php /percorso/progetto/cron/expire-coupons.php
15 1 * * * php /percorso/progetto/cron/generate-sitemap.php
30 2 * * * php /percorso/progetto/cron/cleanup.php
```

## Note sicurezza

- CSRF su form POST
- Session auth con hash Argon2id
- Rate limit login a 5 tentativi per finestra
- Redirect affiliati con minimizzazione IP
- Nessun segreto hardcoded nei file versionati
