<?php
// Couponami - Demo homepage
// index.php

$categories = [
    ['icon' => '🛍️', 'name' => 'Moda', 'count' => 128],
    ['icon' => '💻', 'name' => 'Elettronica', 'count' => 94],
    ['icon' => '✈️', 'name' => 'Viaggi', 'count' => 76],
    ['icon' => '🏠', 'name' => 'Casa', 'count' => 61],
    ['icon' => '💄', 'name' => 'Beauty', 'count' => 53],
    ['icon' => '🍔', 'name' => 'Food', 'count' => 42],
];

$featured = [
    [
        'store' => 'FashionHub',
        'initial' => 'F',
        'type' => 'CODICE',
        'title' => '20% di sconto su tutto',
        'description' => 'Scopri le nuove collezioni e risparmia sul tuo prossimo ordine.',
        'discount' => '-20%',
        'expiry' => 'Scade tra 4 giorni',
        'code' => 'COUPON20'
    ],
    [
        'store' => 'TechWorld',
        'initial' => 'T',
        'type' => 'OFFERTA',
        'title' => 'Fino al 40% su prodotti selezionati',
        'description' => 'Le migliori offerte tech del momento, selezionate per te.',
        'discount' => '-40%',
        'expiry' => 'Offerta attiva',
        'code' => null
    ],
    [
        'store' => 'BeautyLab',
        'initial' => 'B',
        'type' => 'CODICE',
        'title' => '15% di sconto sul primo ordine',
        'description' => 'Un piccolo vantaggio per iniziare a scoprire il mondo BeautyLab.',
        'discount' => '-15%',
        'expiry' => 'Scade tra 7 giorni',
        'code' => 'WELCOME15'
    ],
];

$stores = [
    ['name' => 'Amazon', 'initial' => 'A', 'offers' => 34],
    ['name' => 'Zalando', 'initial' => 'Z', 'offers' => 28],
    ['name' => 'eBay', 'initial' => 'e', 'offers' => 21],
    ['name' => 'Booking', 'initial' => 'B', 'offers' => 19],
    ['name' => 'MediaWorld', 'initial' => 'M', 'offers' => 17],
];

$latest = [
    ['store' => 'HomeStore', 'title' => '10% di sconto su arredamento e casa', 'discount' => '-10%', 'type' => 'CODICE'],
    ['store' => 'SportZone', 'title' => 'Spedizione gratuita sopra 49€', 'discount' => 'FREE', 'type' => 'OFFERTA'],
    ['store' => 'TravelNow', 'title' => 'Offerte hotel fino al 30% in meno', 'discount' => '-30%', 'type' => 'OFFERTA'],
    ['store' => 'GadgetPro', 'title' => '25€ di sconto su ordini selezionati', 'discount' => '-25€', 'type' => 'CODICE'],
];

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Couponami: trova codici sconto, coupon e offerte online dei tuoi negozi preferiti.">
    <meta name="theme-color" content="#6d4aff">
    <title>Couponami — Codici sconto, coupon e offerte</title>

    <style>
        :root {
            --primary: #6d4aff;
            --primary-dark: #5535df;
            --primary-soft: #f0edff;
            --text: #171725;
            --muted: #6e6e7c;
            --border: #e9e9ef;
            --bg: #fafafe;
            --white: #fff;
            --green: #16a36a;
            --shadow: 0 16px 50px rgba(33, 25, 80, .08);
            --radius: 18px;
        }

        * { box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            margin: 0;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--text);
            background: var(--bg);
            line-height: 1.5;
        }

        a { color: inherit; text-decoration: none; }

        button, input { font: inherit; }

        .container {
            width: min(1180px, calc(100% - 40px));
            margin-inline: auto;
        }

        /* Header */
        .topbar {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 20;
        }

        .nav {
            height: 76px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 30px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 23px;
            font-weight: 900;
            letter-spacing: -1px;
        }

        .logo-mark {
            width: 38px;
            height: 38px;
            border-radius: 11px;
            display: grid;
            place-items: center;
            color: white;
            background: linear-gradient(135deg, #7d62ff, #5a38df);
            box-shadow: 0 8px 20px rgba(109, 74, 255, .25);
        }

        .logo span span { color: var(--primary); }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 28px;
            color: #4d4d5b;
            font-size: 14px;
            font-weight: 700;
        }

        .nav-links a:hover { color: var(--primary); }

        .admin-link {
            border: 1px solid var(--border);
            padding: 10px 15px;
            border-radius: 10px;
        }

        /* Hero */
        .hero {
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(circle at 10% 10%, rgba(135, 108, 255, .25), transparent 34%),
                radial-gradient(circle at 90% 30%, rgba(82, 199, 174, .16), transparent 28%),
                linear-gradient(135deg, #f4f1ff 0%, #fff 48%, #f5faff 100%);
            border-bottom: 1px solid var(--border);
        }

        .hero-inner {
            min-height: 510px;
            display: grid;
            grid-template-columns: 1.1fr .9fr;
            align-items: center;
            gap: 50px;
            padding: 70px 0;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #fff;
            border: 1px solid #e7e2ff;
            color: var(--primary);
            padding: 7px 11px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            box-shadow: 0 5px 20px rgba(60, 40, 130, .05);
        }

        .hero h1 {
            margin: 18px 0 15px;
            max-width: 700px;
            font-size: clamp(42px, 6vw, 68px);
            line-height: .99;
            letter-spacing: -3.5px;
        }

        .hero h1 em {
            color: var(--primary);
            font-style: normal;
        }

        .hero p {
            max-width: 600px;
            margin: 0;
            color: var(--muted);
            font-size: 18px;
        }

        .search {
            margin-top: 30px;
            max-width: 650px;
            display: flex;
            align-items: center;
            background: white;
            border: 1px solid #e3e1eb;
            border-radius: 14px;
            padding: 7px;
            box-shadow: 0 15px 35px rgba(45, 35, 100, .09);
        }

        .search-icon {
            width: 44px;
            text-align: center;
            font-size: 19px;
        }

        .search input {
            flex: 1;
            min-width: 0;
            border: 0;
            outline: 0;
            background: transparent;
            color: var(--text);
            padding: 12px 4px;
        }

        .search button {
            border: 0;
            cursor: pointer;
            color: white;
            background: var(--primary);
            border-radius: 10px;
            padding: 12px 21px;
            font-weight: 800;
        }

        .search button:hover { background: var(--primary-dark); }

        .hero-stats {
            margin-top: 25px;
            display: flex;
            gap: 28px;
            flex-wrap: wrap;
        }

        .stat strong {
            display: block;
            font-size: 20px;
        }

        .stat small { color: var(--muted); }

        .hero-card {
            position: relative;
            width: min(390px, 100%);
            justify-self: end;
            background: white;
            border: 1px solid #ebe8f5;
            border-radius: 25px;
            padding: 25px;
            box-shadow: 0 28px 70px rgba(57, 39, 130, .13);
            transform: rotate(2deg);
        }

        .floating-badge {
            position: absolute;
            top: -18px;
            right: -18px;
            background: #191927;
            color: white;
            border-radius: 13px;
            padding: 10px 14px;
            font-size: 12px;
            font-weight: 800;
            box-shadow: 0 10px 25px rgba(0,0,0,.15);
        }

        .mock-store {
            display: flex;
            align-items: center;
            gap: 13px;
            padding-bottom: 18px;
            border-bottom: 1px solid var(--border);
        }

        .store-logo {
            width: 52px;
            height: 52px;
            display: grid;
            place-items: center;
            border-radius: 14px;
            background: #f0edff;
            color: var(--primary);
            font-weight: 900;
            font-size: 21px;
        }

        .mock-store strong { display: block; }
        .mock-store small { color: var(--muted); }

        .mock-offer {
            padding: 24px 0 18px;
        }

        .mock-offer .discount {
            font-size: 43px;
            line-height: 1;
            font-weight: 950;
            letter-spacing: -2px;
            color: var(--primary);
        }

        .mock-offer h3 {
            margin: 12px 0 5px;
            font-size: 19px;
        }

        .mock-offer p {
            margin: 0;
            font-size: 13px;
            color: var(--muted);
        }

        .mock-button {
            width: 100%;
            border: 0;
            border-radius: 11px;
            padding: 13px;
            color: white;
            background: var(--primary);
            font-weight: 800;
        }

        /* Sections */
        section { padding: 76px 0; }

        .section-head {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 28px;
        }

        .section-head h2 {
            margin: 0;
            font-size: 31px;
            letter-spacing: -1.3px;
        }

        .section-head p {
            margin: 6px 0 0;
            color: var(--muted);
        }

        .view-all {
            color: var(--primary);
            font-size: 14px;
            font-weight: 800;
        }

        /* Categories */
        .category-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 14px;
        }

        .category {
            background: white;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px 12px;
            text-align: center;
            transition: .2s ease;
        }

        .category:hover {
            transform: translateY(-3px);
            border-color: #d8d0ff;
            box-shadow: var(--shadow);
        }

        .category-icon {
            width: 54px;
            height: 54px;
            display: grid;
            place-items: center;
            margin: 0 auto 12px;
            border-radius: 15px;
            background: var(--primary-soft);
            font-size: 24px;
        }

        .category strong {
            display: block;
            font-size: 14px;
        }

        .category small {
            color: var(--muted);
            font-size: 12px;
        }

        /* Coupons */
        .coupon-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }

        .coupon {
            background: white;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: 0 8px 25px rgba(30, 30, 50, .03);
            transition: .2s ease;
        }

        .coupon:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }

        .coupon-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .coupon-store {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            font-weight: 800;
        }

        .mini-logo {
            width: 39px;
            height: 39px;
            border-radius: 11px;
            display: grid;
            place-items: center;
            background: #f2f0ff;
            color: var(--primary);
            font-weight: 900;
        }

        .tag {
            border-radius: 999px;
            padding: 5px 9px;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .4px;
        }

        .tag-code {
            background: #eef9f4;
            color: var(--green);
        }

        .tag-offer {
            background: #fff5df;
            color: #b56d00;
        }

        .coupon h3 {
            font-size: 18px;
            line-height: 1.25;
            margin: 19px 0 7px;
        }

        .coupon-desc {
            min-height: 44px;
            margin: 0;
            color: var(--muted);
            font-size: 13px;
        }

        .coupon-bottom {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 21px;
        }

        .coupon-discount {
            min-width: 64px;
            color: var(--primary);
            font-weight: 950;
            font-size: 21px;
        }

        .redeem {
            flex: 1;
            border: 0;
            border-radius: 10px;
            padding: 11px;
            cursor: pointer;
            color: white;
            background: var(--primary);
            font-size: 12px;
            font-weight: 900;
        }

        .redeem:hover { background: var(--primary-dark); }

        .expiry {
            margin-top: 13px;
            color: #90909d;
            font-size: 11px;
        }

        /* Store list */
        .stores {
            background: #f3f1ff;
        }

        .store-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 14px;
        }

        .store {
            background: white;
            border: 1px solid #e5e0ff;
            border-radius: 15px;
            padding: 20px 15px;
            text-align: center;
        }

        .store .mini-logo {
            margin: 0 auto 10px;
            width: 50px;
            height: 50px;
        }

        .store strong {
            display: block;
            font-size: 14px;
        }

        .store small {
            color: var(--muted);
            font-size: 11px;
        }

        /* Latest */
        .latest-layout {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 25px;
            align-items: start;
        }

        .latest-list {
            background: white;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
        }

        .latest-item {
            display: flex;
            align-items: center;
            gap: 17px;
            padding: 19px 20px;
            border-bottom: 1px solid var(--border);
        }

        .latest-item:last-child { border-bottom: 0; }

        .latest-info {
            flex: 1;
            min-width: 0;
        }

        .latest-info strong {
            display: block;
            font-size: 14px;
        }

        .latest-info small {
            color: var(--muted);
            font-size: 11px;
        }

        .latest-discount {
            font-size: 16px;
            color: var(--primary);
            font-weight: 900;
            white-space: nowrap;
        }

        .newsletter {
            background: #1a1930;
            color: white;
            border-radius: var(--radius);
            padding: 28px;
        }

        .newsletter h3 {
            margin: 0 0 8px;
            font-size: 22px;
        }

        .newsletter p {
            margin: 0;
            color: #bdbbd0;
            font-size: 13px;
        }

        .newsletter form {
            margin-top: 18px;
            display: grid;
            gap: 9px;
        }

        .newsletter input {
            border: 1px solid #393750;
            background: #282641;
            color: white;
            outline: 0;
            border-radius: 9px;
            padding: 12px;
        }

        .newsletter button {
            border: 0;
            border-radius: 9px;
            padding: 12px;
            color: white;
            background: var(--primary);
            font-weight: 800;
            cursor: pointer;
        }

        /* CTA */
        .cta {
            padding-top: 20px;
            padding-bottom: 90px;
        }

        .cta-box {
            position: relative;
            overflow: hidden;
            border-radius: 25px;
            padding: 50px;
            text-align: center;
            color: white;
            background: linear-gradient(135deg, #6542ec, #8164ff);
        }

        .cta-box:after {
            content: "";
            position: absolute;
            width: 240px;
            height: 240px;
            border: 45px solid rgba(255,255,255,.08);
            border-radius: 50%;
            right: -60px;
            top: -90px;
        }

        .cta-box h2 {
            position: relative;
            z-index: 1;
            margin: 0 0 9px;
            font-size: 32px;
            letter-spacing: -1px;
        }

        .cta-box p {
            position: relative;
            z-index: 1;
            margin: 0 auto 22px;
            max-width: 620px;
            color: #e8e4ff;
        }

        .cta-button {
            position: relative;
            z-index: 1;
            display: inline-block;
            background: white;
            color: var(--primary);
            border-radius: 10px;
            padding: 12px 20px;
            font-size: 13px;
            font-weight: 900;
        }

        /* Footer */
        footer {
            background: #171624;
            color: white;
            padding: 50px 0 25px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1.5fr repeat(3, 1fr);
            gap: 40px;
            padding-bottom: 40px;
        }

        .footer-brand p {
            color: #a9a7b8;
            max-width: 330px;
            font-size: 13px;
        }

        footer h4 {
            margin: 0 0 15px;
            font-size: 13px;
        }

        footer ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        footer li {
            margin: 9px 0;
            color: #a9a7b8;
            font-size: 12px;
        }

        footer a:hover { color: white; }

        .footer-bottom {
            border-top: 1px solid #2c2a3a;
            padding-top: 22px;
            display: flex;
            justify-content: space-between;
            gap: 20px;
            color: #858393;
            font-size: 11px;
        }

        /* Responsive */
        @media (max-width: 900px) {
            .nav-links { display: none; }

            .hero-inner {
                grid-template-columns: 1fr;
                padding: 55px 0;
            }

            .hero-card {
                justify-self: start;
                transform: none;
            }

            .category-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .coupon-grid {
                grid-template-columns: 1fr 1fr;
            }

            .store-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .latest-layout {
                grid-template-columns: 1fr;
            }

            .footer-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 600px) {
            .container { width: min(100% - 28px, 1180px); }

            .nav { height: 66px; }

            .hero h1 {
                font-size: 45px;
                letter-spacing: -2.5px;
            }

            .hero p { font-size: 16px; }

            .search {
                display: grid;
                grid-template-columns: 35px 1fr;
            }

            .search button {
                grid-column: 1 / -1;
            }

            .category-grid,
            .coupon-grid,
            .store-grid {
                grid-template-columns: 1fr 1fr;
            }

            section { padding: 55px 0; }

            .section-head {
                align-items: start;
                flex-direction: column;
            }

            .latest-item {
                align-items: flex-start;
            }

            .latest-discount {
                display: none;
            }

            .cta-box {
                padding: 35px 20px;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: 28px;
            }

            .footer-bottom {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<header class="topbar">
    <div class="container nav">
        <a class="logo" href="/" aria-label="Couponami Home">
            <span class="logo-mark">%</span>
            <span>coupon<span>ami</span></span>
        </a>

        <nav class="nav-links" aria-label="Navigazione principale">
            <a href="#categorie">Categorie</a>
            <a href="#coupon">Coupon</a>
            <a href="#negozi">Negozi</a>
            <a href="#ultime-offerte">Ultime offerte</a>
            <a class="admin-link" href="/admin">Accedi</a>
        </nav>
    </div>
</header>

<main>

    <section class="hero">
        <div class="container hero-inner">
            <div>
                <div class="eyebrow">✦ Risparmia ad ogni acquisto</div>

                <h1>
                    Trova il tuo prossimo
                    <em>affare.</em>
                </h1>

                <p>
                    Codici sconto, coupon e offerte dei tuoi negozi preferiti.
                    Cerca, scegli e risparmia in pochi secondi.
                </p>

                <form class="search" action="/cerca.php" method="get">
                    <span class="search-icon">⌕</span>
                    <input
                        type="search"
                        name="q"
                        placeholder="Cerca un negozio, prodotto o categoria..."
                        aria-label="Cerca coupon"
                    >
                    <button type="submit">Cerca coupon</button>
                </form>

                <div class="hero-stats">
                    <div class="stat">
                        <strong>1.250+</strong>
                        <small>coupon disponibili</small>
                    </div>
                    <div class="stat">
                        <strong>320+</strong>
                        <small>negozi</small>
                    </div>
                    <div class="stat">
                        <strong>24/7</strong>
                        <small>offerte aggiornate</small>
                    </div>
                </div>
            </div>

            <div class="hero-card">
                <div class="floating-badge">🔥 OFFERTA DEL GIORNO</div>

                <div class="mock-store">
                    <div class="store-logo">F</div>
                    <div>
                        <strong>FashionHub</strong>
                        <small>Moda · 28 offerte attive</small>
                    </div>
                </div>

                <div class="mock-offer">
                    <div class="discount">-20%</div>
                    <h3>20% di sconto su tutto</h3>
                    <p>Valido anche sui nuovi arrivi. Ordine minimo 49€.</p>
                </div>

                <button class="mock-button" type="button"
                        onclick="showCoupon('COUPON20')">
                    Mostra codice
                </button>
            </div>
        </div>
    </section>

    <section id="categorie">
        <div class="container">
            <div class="section-head">
                <div>
                    <h2>Esplora per categoria</h2>
                    <p>Trova rapidamente le offerte che ti interessano.</p>
                </div>
                <a class="view-all" href="/categorie">Vedi tutte →</a>
            </div>

            <div class="category-grid">
                <?php foreach ($categories as $category): ?>
                    <a class="category" href="/categoria/<?php echo e(strtolower($category['name'])); ?>">
                        <div class="category-icon"><?php echo e($category['icon']); ?></div>
                        <strong><?php echo e($category['name']); ?></strong>
                        <small><?php echo (int)$category['count']; ?> offerte</small>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="coupon">
        <div class="container">
            <div class="section-head">
                <div>
                    <h2>Coupon in evidenza</h2>
                    <p>Le offerte che secondo noi non dovresti lasciarti scappare.</p>
                </div>
                <a class="view-all" href="/coupon">Vedi tutti →</a>
            </div>

            <div class="coupon-grid">
                <?php foreach ($featured as $coupon): ?>
                    <article class="coupon">
                        <div class="coupon-top">
                            <div class="coupon-store">
                                <div class="mini-logo"><?php echo e($coupon['initial']); ?></div>
                                <?php echo e($coupon['store']); ?>
                            </div>

                            <span class="tag <?php echo $coupon['type'] === 'CODICE' ? 'tag-code' : 'tag-offer'; ?>">
                                <?php echo e($coupon['type']); ?>
                            </span>
                        </div>

                        <h3><?php echo e($coupon['title']); ?></h3>
                        <p class="coupon-desc"><?php echo e($coupon['description']); ?></p>

                        <div class="coupon-bottom">
                            <div class="coupon-discount"><?php echo e($coupon['discount']); ?></div>

                            <?php if ($coupon['code']): ?>
                                <button
                                    class="redeem"
                                    type="button"
                                    onclick="showCoupon('<?php echo e($coupon['code']); ?>')"
                                >
                                    Mostra codice
                                </button>
                            <?php else: ?>
                                <a class="redeem" href="https://example.com" target="_blank" rel="nofollow sponsored noopener">
                                    Riscatta offerta
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="expiry">◷ <?php echo e($coupon['expiry']); ?></div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="stores" id="negozi">
        <div class="container">
            <div class="section-head">
                <div>
                    <h2>Negozi popolari</h2>
                    <p>Scopri tutti i coupon disponibili per i brand più cercati.</p>
                </div>
                <a class="view-all" href="/negozi">Tutti i negozi →</a>
            </div>

            <div class="store-grid">
                <?php foreach ($stores as $store): ?>
                    <a class="store" href="/negozio/<?php echo e(strtolower($store['name'])); ?>">
                        <div class="mini-logo"><?php echo e($store['initial']); ?></div>
                        <strong><?php echo e($store['name']); ?></strong>
                        <small><?php echo (int)$store['offers']; ?> offerte</small>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="ultime-offerte">
        <div class="container">
            <div class="section-head">
                <div>
                    <h2>Ultime offerte</h2>
                    <p>Le promozioni aggiunte più recentemente.</p>
                </div>
                <a class="view-all" href="/coupon?ordine=recenti">Vedi tutte →</a>
            </div>

            <div class="latest-layout">
                <div class="latest-list">
                    <?php foreach ($latest as $item): ?>
                        <a class="latest-item" href="/coupon">
                            <div class="mini-logo"><?php echo e(substr($item['store'], 0, 1)); ?></div>

                            <div class="latest-info">
                                <strong><?php echo e($item['title']); ?></strong>
                                <small><?php echo e($item['store']); ?> · <?php echo e($item['type']); ?></small>
                            </div>

                            <div class="latest-discount"><?php echo e($item['discount']); ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <aside class="newsletter">
                    <h3>Vuoi risparmiare di più?</h3>
                    <p>
                        Ricevi una selezione delle migliori offerte direttamente
                        nella tua email.
                    </p>

                    <form action="/newsletter.php" method="post">
                        <input type="email" name="email" placeholder="La tua email" required>
                        <button type="submit">Iscrivimi gratis</button>
                    </form>
                </aside>
            </div>
        </div>
    </section>

    <section class="cta">
        <div class="container">
            <div class="cta-box">
                <h2>Un buon affare è sempre una buona idea.</h2>
                <p>
                    Cerca tra migliaia di coupon e offerte e scopri quanto puoi
                    risparmiare sul tuo prossimo acquisto.
                </p>
                <a class="cta-button" href="#coupon">Scopri i coupon</a>
            </div>
        </div>
    </section>

</main>

<footer>
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <a class="logo" href="/">
                    <span class="logo-mark">%</span>
                    <span>coupon<span>ami</span></span>
                </a>
                <p>
                    Couponami raccoglie codici sconto, coupon e offerte online
                    per aiutarti a risparmiare sui tuoi acquisti.
                </p>
            </div>

            <div>
                <h4>Coupon</h4>
                <ul>
                    <li><a href="/coupon">Tutti i coupon</a></li>
                    <li><a href="/coupon?tipo=codice">Codici sconto</a></li>
                    <li><a href="/coupon?tipo=offerta">Offerte</a></li>
                    <li><a href="/coupon?ordine=recenti">Ultimi arrivati</a></li>
                </ul>
            </div>

            <div>
                <h4>Esplora</h4>
                <ul>
                    <li><a href="/categorie">Categorie</a></li>
                    <li><a href="/negozi">Negozi</a></li>
                    <li><a href="/come-funziona">Come funziona</a></li>
                    <li><a href="/blog">Blog</a></li>
                </ul>
            </div>

            <div>
                <h4>Informazioni</h4>
                <ul>
                    <li><a href="/chi-siamo">Chi siamo</a></li>
                    <li><a href="/contatti">Contatti</a></li>
                    <li><a href="/privacy">Privacy</a></li>
                    <li><a href="/cookie">Cookie Policy</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <span>© <?php echo date('Y'); ?> Couponami. Tutti i diritti riservati.</span>
            <span>Alcuni link possono essere link affiliati.</span>
        </div>
    </div>
</footer>

<script>
function showCoupon(code) {
    const message = `Il tuo codice sconto è: ${code}`;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(code).catch(() => {});
    }
    alert(message + "\n\nIl codice è stato copiato negli appunti.");
}
</script>

</body>
</html>
