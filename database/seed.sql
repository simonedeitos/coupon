-- Couponami seed data for development/testing
-- Run after schema.sql

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- Categories
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon`, `sort_order`, `is_active`) VALUES
(1, 'Moda',        'moda',       'Coupon per abbigliamento, accessori e fashion.',             '🛍️', 10, 1),
(2, 'Elettronica', 'elettronica','Offerte su tech, smartphone e accessori.',                   '💻', 20, 1),
(3, 'Viaggi',      'viaggi',     'Sconti su hotel, voli e weekend fuori porta.',               '✈️', 30, 1),
(4, 'Casa',        'casa',       'Promozioni per arredamento e piccoli elettrodomestici.',      '🏠', 40, 1),
(5, 'Beauty',      'beauty',     'Codici per cosmetica, skincare e benessere.',                '💄', 50, 1),
(6, 'Food',        'food',       'Coupon per delivery, grocery e food box.',                   '🍔', 60, 1),
(7, 'Sport',       'sport',      'Abbigliamento sportivo, attrezzatura e accessori outdoor.',  '🏃', 70, 1),
(8, 'Libri',       'libri',      'Sconti su libri, ebook e corsi online.',                     '📚', 80, 1);

-- Stores
INSERT INTO `stores` (`id`, `category_id`, `name`, `slug`, `description`, `website_url`, `is_featured`, `is_active`) VALUES
(1,  1, 'FashionHub',  'fashionhub',  'Moda premium con consegna rapida.',                        'https://example.com/fashionhub',  1, 1),
(2,  2, 'TechWorld',   'techworld',   'Elettronica e gadget con promo settimanali.',               'https://example.com/techworld',   1, 1),
(3,  5, 'BeautyLab',   'beautylab',   'Skincare e make-up con coupon dedicati.',                   'https://example.com/beautylab',   1, 1),
(4,  3, 'TravelNow',   'travelnow',   'Prenotazioni viaggio con sconti stagionali.',               'https://example.com/travelnow',   0, 1),
(5,  4, 'HomeStore',   'homestore',   'Casa e design per ogni stanza.',                            'https://example.com/homestore',   0, 1),
(6,  7, 'SportZone',   'sportzone',   'Abbigliamento sportivo e attrezzatura.',                    'https://example.com/sportzone',   0, 1),
(7,  6, 'FoodBox',     'foodbox',     'Meal kit settimanali con ingredienti freschi.',             'https://example.com/foodbox',     0, 1),
(8,  2, 'GadgetPro',   'gadgetpro',   'Gadget high-tech e accessori per smart home.',             'https://example.com/gadgetpro',   0, 1),
(9,  1, 'StylePlus',   'styleplus',   'Outlet online di grandi brand di moda.',                   'https://example.com/styleplus',   0, 1),
(10, 8, 'ReadMore',    'readmore',    'Libreria online con sconti su tutte le categorie.',         'https://example.com/readmore',    0, 1);

-- Affiliate network
INSERT INTO `affiliate_networks` (`id`, `name`, `slug`, `api_base_url`, `publisher_id`, `is_active`) VALUES
(1, 'TradeDoubler', 'tradedoubler', 'https://api.tradedoubler.com/1.0/', '123456', 1);

-- Affiliate programs
INSERT INTO `affiliate_programs` (`id`, `network_id`, `store_id`, `external_program_id`, `name`, `status`, `last_synced_at`) VALUES
(1, 1, 1, 'td-fh-001',  'FashionHub Affiliate',   'ACTIVE',  NOW() - INTERVAL 12 MINUTE),
(2, 1, 2, 'td-tw-002',  'TechWorld CPA',           'ACTIVE',  NOW() - INTERVAL 12 MINUTE),
(3, 1, 4, 'td-tn-003',  'TravelNow Bookings',      'PAUSED',  NOW() - INTERVAL 2 HOUR);

-- Offers
INSERT INTO `offers` (`id`, `store_id`, `category_id`, `title`, `slug`, `description`, `offer_type`, `coupon_code`, `affiliate_url`, `external_id`, `dedupe_hash`, `status`, `priority`, `badge`, `expires_at`, `is_featured`) VALUES
(1,  1, 1, '20% di sconto su tutto FashionHub',                'fashionhub-20-sconto',       'Valido anche sui nuovi arrivi con minimo d\'ordine 49€.',                                  'CODICE',  'COUPON20',  'https://example.com/fashionhub?coupon=20',      'td-1001', SHA1('fashionhub-20'),   'ACTIVE',    90, 'Hot',      DATE_ADD(CURDATE(), INTERVAL 4  DAY), 1),
(2,  2, 2, 'Fino al 40% su prodotti selezionati TechWorld',    'techworld-fino-40',           'Sconti su notebook, monitor e accessori smart home.',                                     'OFFERTA', '',          'https://example.com/techworld?promo=40',        'td-1002', SHA1('techworld-40'),    'ACTIVE',    95, 'Top Deal', DATE_ADD(CURDATE(), INTERVAL 7  DAY), 1),
(3,  3, 5, '15% di sconto sul primo ordine BeautyLab',         'beautylab-primo-ordine',      'Perfetto per iniziare con la linea skincare premium.',                                    'CODICE',  'WELCOME15', 'https://example.com/beautylab?welcome=15',      'td-1003', SHA1('beautylab-welcome'),'ACTIVE',    82, 'Nuovo',    DATE_ADD(CURDATE(), INTERVAL 10 DAY), 1),
(4,  4, 3, 'Offerte hotel fino al 30% in meno',                'travelnow-hotel-30',          'Weekend europei e city break con cancellazione flessibile.',                              'OFFERTA', '',          'https://example.com/travelnow?hotel=30',        'td-1004', SHA1('travelnow-30'),    'ACTIVE',    75, 'Travel',   DATE_ADD(CURDATE(), INTERVAL 14 DAY), 0),
(5,  5, 4, '10% di sconto su arredamento e casa',              'homestore-arredamento-10',    'Promo valida sui bestseller della collezione autunno.',                                   'CODICE',  'HOME10',    'https://example.com/homestore?home=10',         'td-1005', SHA1('homestore-10'),    'SCHEDULED', 64, 'Casa',     DATE_ADD(CURDATE(), INTERVAL 20 DAY), 0),
(6,  6, 1, 'Spedizione gratuita sopra 49€',                    'sportzone-spedizione-gratis', 'Perfetto per ordini multipli e collezioni running.',                                      'OFFERTA', '',          'https://example.com/sportzone?free-shipping=1', 'td-1006', SHA1('sportzone-free'),  'ACTIVE',    68, 'Free Ship',DATE_ADD(CURDATE(), INTERVAL 30 DAY), 0),
(7,  2, 2, '25€ di sconto su smartphone selezionati',          'techworld-25-euro-smartphone','Utilizza il codice su ordini superiori a 299€.',                                          'CODICE',  'TECH25',    'https://example.com/techworld?phone=25',        'td-1007', SHA1('techworld-25'),    'ACTIVE',    73, 'Smartphone',DATE_ADD(CURDATE(), INTERVAL 8  DAY), 0),
(8,  7, 6, '12€ di sconto sulla prima food box',               'foodbox-box-benvenuto',       'Ideale per provare il servizio di meal kit settimanale.',                                 'CODICE',  'FOOD12',    'https://example.com/foodbox?welcome=12',        'td-1008', SHA1('foodbox-12'),      'EXPIRED',   38, 'Food',     DATE_SUB(CURDATE(), INTERVAL 2  DAY), 0),
(9,  8, 2, 'Sconto 35€ su smart speaker e hub domotica',       'gadgetpro-smart-speaker-35',  'Valido sull\'intera gamma di dispositivi per la casa intelligente.',                     'CODICE',  'GADGET35',  'https://example.com/gadgetpro?speaker=35',      'td-1009', SHA1('gadgetpro-35'),    'ACTIVE',    70, 'Smart',    DATE_ADD(CURDATE(), INTERVAL 5  DAY), 0),
(10, 9, 1, '25% off prima spesa StylePlus',                    'styleplus-primo-acquisto-25', 'Coupon di benvenuto applicabile a qualsiasi prodotto in outlet.',                        'CODICE',  'STYLE25',   'https://example.com/styleplus?first=25',        'td-1010', SHA1('styleplus-25'),    'ACTIVE',    78, 'Outlet',   DATE_ADD(CURDATE(), INTERVAL 6  DAY), 0),
(11,10, 8, 'Libri -20% con codice LEGGI20',                    'readmore-libri-20',           'Sconto su tutti i libri cartacei e ebook disponibili sul sito.',                         'CODICE',  'LEGGI20',   'https://example.com/readmore?books=20',         'td-1011', SHA1('readmore-20'),     'ACTIVE',    55, 'Lettura',  DATE_ADD(CURDATE(), INTERVAL 15 DAY), 0),
(12, 1, 1, 'Saldi fino al -50% su capi selezionati FashionHub','fashionhub-saldi-50',         'Saldi stagionali su centinaia di articoli con spedizione inclusa sopra 69€.',           'OFFERTA', '',          'https://example.com/fashionhub?sale=50',        'td-1012', SHA1('fashionhub-50'),   'ACTIVE',    85, 'Saldi',    DATE_ADD(CURDATE(), INTERVAL 3  DAY), 0);

-- Simulated status log entry for expired offer
INSERT INTO `offer_status_log` (`offer_id`, `old_status`, `new_status`, `reason`, `created_at`) VALUES
(8, 'ACTIVE', 'EXPIRED', 'Auto-expired by cron', DATE_SUB(NOW(), INTERVAL 2 DAY));

SET foreign_key_checks = 1;
