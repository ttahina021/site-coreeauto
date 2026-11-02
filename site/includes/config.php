<?php
// Paramètres généraux du site Corée Auto.
// Modifiez ces valeurs selon vos besoins.

const SITE_NAME = 'Corée Auto';
const SITE_SLOGAN = 'Vente pièces détachées d’origine coréenne';
const SITE_EMAIL = 'contact@coreeauto.mg';
const SITE_URL = 'www.coreeauto.mg';
const DEVISE = 'Ar';

// Numéro WhatsApp principal (format international sans + ni espaces)
const WHATSAPP = '261335177778';

// 'carte' : texte recherché sur Google Maps (adresse, ou coordonnées GPS « -18.89,47.52 »)
const MAGASINS = [
    ['nom' => 'Ankasina 67ha', 'tel' => '033 51 777 78', 'adresse' => 'Rue Boulevard de l’Europe, 67 Ha, Antananarivo',
        'carte' => '-18.8968576,47.5112374'],
    ['nom' => 'Anosizato Est', 'tel' => '033 60 777 77', 'adresse' => 'Anosizato Est, Antananarivo',
        'carte' => '-18.933064,47.505547'],
    ['nom' => 'Tamatave (Bazar be)', 'tel' => '033 72 606 72', 'adresse' => 'Bazar be, Toamasina',
        'carte' => '-18.1571227,49.4103809'],
];

const HORAIRES = [
    'Lundi au vendredi' => '08:00 - 17:00',
    'Samedi' => '08:00 - 14:00',
    'Dimanche' => 'Fermé',
];

// Prestations de l'atelier (page Atelier et formulaire de rendez-vous)
const SERVICES_ATELIER = [
    ['titre' => 'Diagnostic électronique', 'texte' => 'Lecture des codes défaut à la valise, recherche de panne moteur, ABS, airbag, boîte automatique.'],
    ['titre' => 'Entretien & vidange', 'texte' => 'Vidange moteur, filtres, bougies, courroie de distribution, contrôle des niveaux.'],
    ['titre' => 'Freinage', 'texte' => 'Plaquettes, disques, mâchoires, maître-cylindre, purge du circuit de freinage.'],
    ['titre' => 'Embrayage & transmission', 'texte' => 'Remplacement du kit d’embrayage, cardans, roulements, soufflets.'],
    ['titre' => 'Suspension & direction', 'texte' => 'Amortisseurs, rotules, biellettes, silentblocs, réglage du train avant.'],
];

const MARQUES = ['Hyundai', 'Kia', 'SsangYong', 'Daewoo', 'Chevrolet', 'Genesis'];

const CATEGORIES = [
    'Freinage', 'Moteur', 'Suspension', 'Direction', 'Transmission',
    'Embrayage', 'Filtration', 'Électricité', 'Refroidissement', 'Carrosserie', 'Injection',
];

define('ROOT_DIR', dirname(__DIR__));
define('DB_PATH', ROOT_DIR . '/data/coreeauto.sqlite');
define('UPLOAD_DIR', ROOT_DIR . '/uploads');
const UPLOAD_MAX_BYTES = 5 * 1024 * 1024;
