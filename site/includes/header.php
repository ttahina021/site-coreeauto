<?php
/** @var string $titre        Titre de la page (optionnel) */
/** @var string $page         Identifiant de la page active (optionnel) */
/** @var string $description  Description pour Google (optionnel) */
/** @var string $canonical    Adresse officielle de la page, ex. url('atelier') (optionnel) */
/** @var string $robots       « noindex » pour les pages à ne pas référencer (optionnel) */
/** @var string $ogImage      Image de partage réseaux sociaux (optionnel) */
/** @var array  $jsonLd       Données structurées supplémentaires (optionnel) */
$titre = $titre ?? SITE_NAME;
$page = $page ?? '';
$q = $_GET['q'] ?? '';
$titreComplet = $titre === SITE_NAME ? SITE_NAME . ' | Pièces détachées coréennes à Madagascar' : $titre . ' | ' . SITE_NAME;
$description = $description ?? 'Corée Auto : pièces détachées d’origine coréenne pour Hyundai, Kia, SsangYong, Daewoo. Garage et atelier mécanique. Magasins à Antananarivo et Tamatave.';
$description = mb_strimwidth(trim(preg_replace('/\s+/', ' ', $description)), 0, 160, '…');
$canonicalAbs = isset($canonical) ? url_absolue($canonical) : null;
$ogImageAbs = url_absolue($ogImage ?? url('assets/img/couverture.jpg'));

// Fiche entreprise pour Google (magasins, horaires, téléphones)
$magasinsLd = [];
foreach (MAGASINS as $m) {
    $magasinsLd[] = [
        '@type' => 'AutoPartsStore',
        'name' => SITE_NAME . ' ' . $m['nom'],
        'telephone' => '+261' . substr(str_replace(' ', '', $m['tel']), 1),
        'address' => ['@type' => 'PostalAddress', 'streetAddress' => $m['adresse'], 'addressCountry' => 'MG'],
        'openingHours' => ['Mo-Fr 08:00-17:00', 'Sa 08:00-14:00'],
        'url' => url_absolue(url('magasins')),
    ];
}
$ldBase = [
    '@context' => 'https://schema.org',
    '@type' => 'AutoPartsStore',
    'name' => SITE_NAME,
    'description' => SITE_SLOGAN,
    'url' => url_absolue(url('')),
    'logo' => url_absolue(url('assets/img/logo-header.png')),
    'image' => url_absolue(url('assets/img/couverture.jpg')),
    'email' => SITE_EMAIL,
    'telephone' => '+261335177778',
    'priceRange' => '$$',
    'currenciesAccepted' => 'MGA',
    'areaServed' => 'Madagascar',
    'department' => $magasinsLd,
    'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => url_absolue(url('catalogue')) . '?q={search_term_string}',
        'query-input' => 'required name=search_term_string',
    ],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titreComplet) ?></title>
    <meta name="description" content="<?= e($description) ?>">
    <?php if (!empty($robots)): ?><meta name="robots" content="<?= e($robots) ?>"><?php endif; ?>
    <?php if ($canonicalAbs): ?><link rel="canonical" href="<?= e($canonicalAbs) ?>"><?php endif; ?>
    <meta name="theme-color" content="#193c90">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e(SITE_NAME) ?>">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:title" content="<?= e($titreComplet) ?>">
    <meta property="og:description" content="<?= e($description) ?>">
    <meta property="og:image" content="<?= e($ogImageAbs) ?>">
    <?php if ($canonicalAbs): ?><meta property="og:url" content="<?= e($canonicalAbs) ?>"><?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">

    <link rel="icon" type="image/png" sizes="32x32" href="<?= url('assets/img/favicon-32.png') ?>">
    <link rel="icon" type="image/png" sizes="64x64" href="<?= url('assets/img/favicon.png') ?>">
    <link rel="apple-touch-icon" href="<?= url('assets/img/apple-touch-icon.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    <script type="application/ld+json"><?= json_encode($ldBase, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
    <?php foreach ($jsonLd ?? [] as $ld): ?>
    <script type="application/ld+json"><?= json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
    <?php endforeach; ?>
</head>
<body>

<div class="topbar">
    <div class="conteneur topbar-contenu">
        <span>Livraison et retrait dans nos 2 magasins · Ouvert 6j/7</span>
        <span class="topbar-droite">
            <a href="tel:+261335177778">033 51 777 78</a>
            <a href="mailto:<?= e(SITE_EMAIL) ?>"><?= e(SITE_EMAIL) ?></a>
        </span>
    </div>
</div>

<header class="entete">
    <div class="conteneur entete-contenu">
        <a href="<?= url('') ?>" class="logo" aria-label="Accueil Corée Auto">
            <img src="<?= url('assets/img/logo-header.png') ?>" alt="Corée Auto, vente pièces détachées d’origine coréenne">
        </a>

        <form class="recherche" action="<?= url('catalogue') ?>" method="get" role="search">
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Rechercher une pièce, une marque, un modèle…" aria-label="Rechercher">
            <button type="submit" aria-label="Lancer la recherche">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
            </button>
        </form>

        <a href="<?= url('panier') ?>" class="btn-panier" aria-label="Voir le panier">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.5L21 8H6"/><circle cx="10" cy="20" r="1.5"/><circle cx="17" cy="20" r="1.5"/></svg>
            <span>Panier</span>
            <?php if (panier_nb() > 0): ?><span class="pastille-panier"><?= panier_nb() ?></span><?php endif; ?>
        </a>

        <button class="btn-menu" aria-label="Menu" onclick="document.body.classList.toggle('menu-ouvert')">
            <span></span><span></span><span></span>
        </button>
    </div>

    <nav class="navigation">
        <div class="conteneur">
            <a href="<?= url('') ?>" class="<?= $page === 'accueil' ? 'actif' : '' ?>">Accueil</a>
            <a href="<?= url('catalogue') ?>" class="<?= $page === 'catalogue' ? 'actif' : '' ?>">Catalogue</a>
            <?php if (promotions_visibles()): ?>
            <a href="<?= url('promotions') ?>" class="lien-promo <?= $page === 'promos' ? 'actif' : '' ?>">Promotions</a>
            <?php endif; ?>
            <?php foreach (array_slice(MARQUES, 0, 4) as $m): ?>
                <a href="<?= lien_marque($m) ?>"><?= e($m) ?></a>
            <?php endforeach; ?>
            <a href="<?= url('atelier') ?>" class="<?= $page === 'atelier' ? 'actif' : '' ?>">Atelier</a>
            <a href="<?= url('magasins') ?>" class="<?= $page === 'contact' ? 'actif' : '' ?>">Nos magasins</a>
        </div>
    </nav>
</header>

<main>
<div class="conteneur"><?= flashes() ?></div>
