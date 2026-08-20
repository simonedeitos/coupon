<?php

declare(strict_types=1);

return [
    'name' => 'Couponami',
    'env' => getenv('APP_ENV') ?: 'production',
    'base_url' => rtrim(getenv('APP_URL') ?: 'https://couponami.it', '/'),
    'app_name' => 'Couponami',
    'app_description' => 'Trova codici sconto, coupon e offerte online',
    'app_locale' => 'it_IT',
    'asset_url' => '/assets',
    'css_path' => '/assets/css',
    'js_path' => '/assets/js',
    'images_path' => '/assets/images',
    'timezone' => 'Europe/Rome',
    'contact_email' => getenv('CONTACT_EMAIL') ?: 'info@couponami.it',
    'admin_users' => [[
        'id' => 1,
        'username' => getenv('ADMIN_USERNAME') ?: 'couponami-admin',
        'password_hash' => getenv('ADMIN_PASSWORD_HASH') ?: '',
        'role' => 'SUPER_ADMIN',
        'display_name' => getenv('ADMIN_DISPLAY_NAME') ?: 'Couponami Admin',
        'email' => getenv('ADMIN_EMAIL') ?: 'admin@example.test',
    ]],
    'roles' => ['SUPER_ADMIN', 'ADMIN', 'EDITOR', 'ANALYTICS'],
    'static_pages' => [
        'come-funziona' => ['title' => 'Come funziona Couponami', 'summary' => 'Trova coupon, apri il codice, completa l\'acquisto e risparmia in pochi passaggi.', 'sections' => [['title' => '1. Cerca il brand giusto', 'body' => 'Naviga tra categorie, negozi e offerte selezionate dal team editoriale.'], ['title' => '2. Apri il coupon o l\'offerta', 'body' => 'Per i codici sconto mostriamo il codice in una modale dedicata e lo copiamo negli appunti.'], ['title' => '3. Vai al partner', 'body' => 'Il redirect /go/{offer_id} traccia il click in modo GDPR-friendly e ti porta al partner affiliato.']]],
        'chi-siamo' => ['title' => 'Chi siamo', 'summary' => 'Couponami è una piattaforma editoriale orientata a performance, qualità dei coupon e dati verificabili.', 'sections' => [['title' => 'Missione', 'body' => 'Aiutiamo gli utenti a trovare offerte affidabili con un backend amministrativo leggero e controlli automatici.'], ['title' => 'Metodo', 'body' => 'Ogni offerta viene classificata per stato, priorità, performance e qualità del partner di affiliazione.'], ['title' => 'Trasparenza', 'body' => 'Indichiamo chiaramente i link affiliati e applichiamo minimizzazione IP ai dati di tracking.']]],
        'privacy' => ['title' => 'Privacy Policy', 'summary' => 'Informativa sintetica su trattamento dati, minimizzazione IP, retention e diritti dell\'interessato.', 'sections' => [['title' => 'Dati raccolti', 'body' => 'Memorizziamo click anonimizzati, dati tecnici essenziali e preferenze di navigazione strettamente necessarie al servizio.'], ['title' => 'Retention', 'body' => 'I dati granulari vengono anonimizzati automaticamente dai job di cleanup per rispettare i principi GDPR.'], ['title' => 'Diritti', 'body' => 'Puoi richiedere accesso, rettifica o cancellazione scrivendo al contatto indicato nelle impostazioni del sito.']]],
        'cookie' => ['title' => 'Cookie Policy', 'summary' => 'Uso di cookie tecnici, cookie di sessione e indicatori di traffico strettamente funzionali.', 'sections' => [['title' => 'Cookie tecnici', 'body' => 'Gestiscono autenticazione admin, CSRF e preferenze di interfaccia.'], ['title' => 'Cookie analytics', 'body' => 'I report interni usano identificatori minimizzati e non combinano dati per profilazione individuale.'], ['title' => 'Gestione consenso', 'body' => 'Puoi disabilitare i cookie non essenziali dal tuo browser senza compromettere la consultazione dei coupon pubblici.']]],
    ],
    'seed' => [
        'categories' => [
            ['id' => 1, 'slug' => 'moda', 'name' => 'Moda', 'icon' => '🛍️', 'description' => 'Coupon per abbigliamento, accessori e fashion.', 'offer_count' => 6],
            ['id' => 2, 'slug' => 'elettronica', 'name' => 'Elettronica', 'icon' => '💻', 'description' => 'Offerte su tech, smartphone e accessori.', 'offer_count' => 5],
            ['id' => 3, 'slug' => 'viaggi', 'name' => 'Viaggi', 'icon' => '✈️', 'description' => 'Sconti su hotel, voli e weekend fuori porta.', 'offer_count' => 4],
            ['id' => 4, 'slug' => 'casa', 'name' => 'Casa', 'icon' => '🏠', 'description' => 'Promozioni per arredamento e piccoli elettrodomestici.', 'offer_count' => 3],
            ['id' => 5, 'slug' => 'beauty', 'name' => 'Beauty', 'icon' => '💄', 'description' => 'Codici per cosmetica, skincare e benessere.', 'offer_count' => 4],
            ['id' => 6, 'slug' => 'food', 'name' => 'Food', 'icon' => '🍔', 'description' => 'Coupon per delivery, grocery e food box.', 'offer_count' => 3]
        ],
        'stores' => [
            ['id' => 1, 'slug' => 'fashionhub', 'name' => 'FashionHub', 'initial' => 'F', 'description' => 'Moda premium con consegna rapida.', 'website' => 'https://example.com/fashionhub', 'offers_count' => 4, 'featured' => true],
            ['id' => 2, 'slug' => 'techworld', 'name' => 'TechWorld', 'initial' => 'T', 'description' => 'Elettronica e gadget con promo settimanali.', 'website' => 'https://example.com/techworld', 'offers_count' => 3, 'featured' => true],
            ['id' => 3, 'slug' => 'beautylab', 'name' => 'BeautyLab', 'initial' => 'B', 'description' => 'Skincare e make-up con coupon dedicati.', 'website' => 'https://example.com/beautylab', 'offers_count' => 3, 'featured' => true],
            ['id' => 4, 'slug' => 'travelnow', 'name' => 'TravelNow', 'initial' => 'T', 'description' => 'Prenotazioni viaggio con sconti stagionali.', 'website' => 'https://example.com/travelnow', 'offers_count' => 2, 'featured' => false],
            ['id' => 5, 'slug' => 'homestore', 'name' => 'HomeStore', 'initial' => 'H', 'description' => 'Casa e design per ogni stanza.', 'website' => 'https://example.com/homestore', 'offers_count' => 2, 'featured' => false],
            ['id' => 6, 'slug' => 'sportzone', 'name' => 'SportZone', 'initial' => 'S', 'description' => 'Abbigliamento sportivo e attrezzatura.', 'website' => 'https://example.com/sportzone', 'offers_count' => 2, 'featured' => false]
        ],
        'offers' => [
            ['id' => 1, 'slug' => 'fashionhub-20-sconto', 'store_id' => 1, 'category_id' => 1, 'type' => 'CODICE', 'title' => '20% di sconto su tutto FashionHub', 'description' => 'Valido anche sui nuovi arrivi con minimo d\'ordine 49€.', 'discount' => '-20%', 'code' => 'COUPON20', 'affiliate_url' => 'https://example.com/fashionhub?coupon=20', 'status' => 'ACTIVE', 'featured' => true, 'badge' => 'Hot', 'expires_at' => date('Y-m-d', strtotime('+4 days')), 'clicks' => 182, 'priority' => 90, 'external_id' => 'td-1001', 'hash' => sha1('fashionhub-20')],
            ['id' => 2, 'slug' => 'techworld-fino-40', 'store_id' => 2, 'category_id' => 2, 'type' => 'OFFERTA', 'title' => 'Fino al 40% su prodotti selezionati TechWorld', 'description' => 'Sconti su notebook, monitor e accessori smart home.', 'discount' => '-40%', 'code' => '', 'affiliate_url' => 'https://example.com/techworld?promo=40', 'status' => 'ACTIVE', 'featured' => true, 'badge' => 'Top Deal', 'expires_at' => date('Y-m-d', strtotime('+7 days')), 'clicks' => 241, 'priority' => 95, 'external_id' => 'td-1002', 'hash' => sha1('techworld-40')],
            ['id' => 3, 'slug' => 'beautylab-primo-ordine', 'store_id' => 3, 'category_id' => 5, 'type' => 'CODICE', 'title' => '15% di sconto sul primo ordine BeautyLab', 'description' => 'Perfetto per iniziare con la linea skincare premium.', 'discount' => '-15%', 'code' => 'WELCOME15', 'affiliate_url' => 'https://example.com/beautylab?welcome=15', 'status' => 'ACTIVE', 'featured' => true, 'badge' => 'Nuovo', 'expires_at' => date('Y-m-d', strtotime('+10 days')), 'clicks' => 119, 'priority' => 82, 'external_id' => 'td-1003', 'hash' => sha1('beautylab-welcome')],
            ['id' => 4, 'slug' => 'travelnow-hotel-30', 'store_id' => 4, 'category_id' => 3, 'type' => 'OFFERTA', 'title' => 'Offerte hotel fino al 30% in meno', 'description' => 'Weekend europei e city break con cancellazione flessibile.', 'discount' => '-30%', 'code' => '', 'affiliate_url' => 'https://example.com/travelnow?hotel=30', 'status' => 'ACTIVE', 'featured' => false, 'badge' => 'Travel', 'expires_at' => date('Y-m-d', strtotime('+14 days')), 'clicks' => 94, 'priority' => 75, 'external_id' => 'td-1004', 'hash' => sha1('travelnow-30')],
            ['id' => 5, 'slug' => 'homestore-arredamento-10', 'store_id' => 5, 'category_id' => 4, 'type' => 'CODICE', 'title' => '10% di sconto su arredamento e casa', 'description' => 'Promo valida sui bestseller della collezione autunno.', 'discount' => '-10%', 'code' => 'HOME10', 'affiliate_url' => 'https://example.com/homestore?home=10', 'status' => 'SCHEDULED', 'featured' => false, 'badge' => 'Casa', 'expires_at' => date('Y-m-d', strtotime('+20 days')), 'clicks' => 57, 'priority' => 64, 'external_id' => 'td-1005', 'hash' => sha1('homestore-10')],
            ['id' => 6, 'slug' => 'sportzone-spedizione-gratis', 'store_id' => 6, 'category_id' => 1, 'type' => 'OFFERTA', 'title' => 'Spedizione gratuita sopra 49€', 'description' => 'Perfetto per ordini multipli e collezioni running.', 'discount' => 'FREE', 'code' => '', 'affiliate_url' => 'https://example.com/sportzone?free-shipping=1', 'status' => 'ACTIVE', 'featured' => false, 'badge' => 'Free Ship', 'expires_at' => date('Y-m-d', strtotime('+30 days')), 'clicks' => 88, 'priority' => 68, 'external_id' => 'td-1006', 'hash' => sha1('sportzone-free')],
            ['id' => 7, 'slug' => 'techworld-25-euro-smartphone', 'store_id' => 2, 'category_id' => 2, 'type' => 'CODICE', 'title' => '25€ di sconto su smartphone selezionati', 'description' => 'Utilizza il codice su ordini superiori a 299€.', 'discount' => '-25€', 'code' => 'TECH25', 'affiliate_url' => 'https://example.com/techworld?phone=25', 'status' => 'ACTIVE', 'featured' => false, 'badge' => 'Smartphone', 'expires_at' => date('Y-m-d', strtotime('+8 days')), 'clicks' => 136, 'priority' => 73, 'external_id' => 'td-1007', 'hash' => sha1('techworld-25')],
            ['id' => 8, 'slug' => 'foodbox-box-benvenuto', 'store_id' => 6, 'category_id' => 6, 'type' => 'CODICE', 'title' => '12€ di sconto sulla prima food box', 'description' => 'Ideale per provare il servizio di meal kit settimanale.', 'discount' => '-12€', 'code' => 'FOOD12', 'affiliate_url' => 'https://example.com/foodbox?welcome=12', 'status' => 'EXPIRED', 'featured' => false, 'badge' => 'Food', 'expires_at' => date('Y-m-d', strtotime('-2 days')), 'clicks' => 44, 'priority' => 38, 'external_id' => 'td-1008', 'hash' => sha1('foodbox-12')]
        ],
        'affiliate_programs' => [
            ['id' => 1, 'network' => 'TradeDoubler', 'program' => 'FashionHub Affiliate', 'status' => 'ACTIVE', 'last_sync' => date('Y-m-d H:i', strtotime('-12 minutes'))],
            ['id' => 2, 'network' => 'TradeDoubler', 'program' => 'TechWorld CPA', 'status' => 'ACTIVE', 'last_sync' => date('Y-m-d H:i', strtotime('-12 minutes'))],
            ['id' => 3, 'network' => 'TradeDoubler', 'program' => 'TravelNow Bookings', 'status' => 'PAUSED', 'last_sync' => date('Y-m-d H:i', strtotime('-2 hours'))]
        ],
        'affiliate_imports' => [
            ['id' => 1, 'source' => 'TradeDoubler', 'status' => 'UPDATED', 'processed' => 42, 'duplicates' => 6, 'errors' => 0, 'created_at' => date('Y-m-d H:i', strtotime('-15 minutes'))],
            ['id' => 2, 'source' => 'TradeDoubler', 'status' => 'DUPLICATE', 'processed' => 8, 'duplicates' => 8, 'errors' => 0, 'created_at' => date('Y-m-d H:i', strtotime('-30 minutes'))],
            ['id' => 3, 'source' => 'TradeDoubler', 'status' => 'ERROR', 'processed' => 17, 'duplicates' => 1, 'errors' => 2, 'created_at' => date('Y-m-d H:i', strtotime('-1 day'))]
        ],
        'verification' => [
            ['store' => 'FashionHub', 'offer' => '20% di sconto su tutto FashionHub', 'status' => 'VALID', 'checked_at' => date('Y-m-d H:i', strtotime('-25 minutes'))],
            ['store' => 'TechWorld', 'offer' => 'Fino al 40% su prodotti selezionati TechWorld', 'status' => 'VALID', 'checked_at' => date('Y-m-d H:i', strtotime('-40 minutes'))],
            ['store' => 'FoodBox', 'offer' => '12€ di sconto sulla prima food box', 'status' => 'EXPIRED', 'checked_at' => date('Y-m-d H:i', strtotime('-1 hour'))]
        ]
    ]
];
