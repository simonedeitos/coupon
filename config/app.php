<?php

declare(strict_types=1);

return [
    'name' => 'Couponami',
    'env' => getenv('APP_ENV') ?: 'production',
    'base_url' => rtrim(getenv('APP_URL') ?: 'https://couponami.it', '/'),
    'app_description' => 'Trova codici sconto, coupon e offerte online',
    'app_locale' => 'it_IT',
    'asset_url' => '/assets',
    'css_path' => '/assets/css',
    'js_path' => '/assets/js',
    'images_path' => '/assets/images',
    'timezone' => 'Europe/Rome',
    'contact_email' => getenv('CONTACT_EMAIL') ?: 'info@couponami.it',
    'admin_users' => [],
    'roles' => ['SUPER_ADMIN', 'ADMIN', 'EDITOR', 'ANALYTICS'],
    'static_pages' => [
        'come-funziona' => ['title' => 'Come funziona Couponami', 'summary' => 'Trova coupon, apri il codice, completa l\'acquisto e risparmia in pochi passaggi.', 'sections' => [['title' => '1. Cerca', 'body' => 'Sfoglia categorie e negozi per trovare l\'offerta più adatta.'], ['title' => '2. Copia il codice', 'body' => 'Clicca su "Mostra codice" per copiare il coupon negli appunti.'], ['title' => '3. Risparmia', 'body' => 'Applica il codice al checkout del negozio e completa l\'acquisto.']]],
        'chi-siamo' => ['title' => 'Chi siamo', 'summary' => 'Couponami è una piattaforma editoriale orientata a performance, qualità dei coupon e dati verificabili.', 'sections' => [['title' => 'La nostra missione', 'body' => 'Selezionare e verificare le migliori offerte online per aiutarti a risparmiare.']]],
        'privacy' => ['title' => 'Privacy Policy', 'summary' => 'Informativa sintetica su trattamento dati, minimizzazione IP, retention e diritti dell\'interessato.', 'sections' => [['title' => 'Trattamento dati', 'body' => 'I dati raccolti sono utilizzati esclusivamente per il funzionamento del servizio.']]],
        'cookie' => ['title' => 'Cookie Policy', 'summary' => 'Uso di cookie tecnici, cookie di sessione e indicatori di traffico strettamente funzionali.', 'sections' => [['title' => 'Cookie tecnici', 'body' => 'Utilizziamo solo cookie necessari al funzionamento del sito.']]],
    ],
];