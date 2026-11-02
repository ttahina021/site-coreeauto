<?php
// Plan du site pour les moteurs de recherche (servi à l'adresse /sitemap.xml).
require_once __DIR__ . '/includes/bootstrap.php';

$adresses = [];
$ajouter = function (string $chemin, string $priorite, string $frequence, ?string $modif = null) use (&$adresses) {
    $adresses[] = ['loc' => url_absolue($chemin), 'priority' => $priorite, 'changefreq' => $frequence, 'lastmod' => $modif];
};

$ajouter(url(''), '1.0', 'daily');
$ajouter(url('catalogue'), '0.9', 'daily');
if (promotions_visibles()) {
    $ajouter(url('promotions'), '0.8', 'daily');
}
$ajouter(url('arrivages'), '0.8', 'weekly');
$ajouter(url('atelier'), '0.7', 'monthly');
$ajouter(url('magasins'), '0.7', 'monthly');

// Marques et catégories qui ont au moins une pièce en ligne
$marques = db()->query('SELECT DISTINCT marque FROM pieces WHERE actif = 1')->fetchAll(PDO::FETCH_COLUMN);
foreach (MARQUES as $m) {
    if (in_array($m, $marques, true)) {
        $ajouter(lien_marque($m), '0.8', 'weekly');
    }
}
$categories = db()->query('SELECT DISTINCT categorie FROM pieces WHERE actif = 1')->fetchAll(PDO::FETCH_COLUMN);
foreach (CATEGORIES as $c) {
    if (in_array($c, $categories, true)) {
        $ajouter(lien_categorie($c), '0.7', 'weekly');
    }
}

// Chaque pièce
foreach (db()->query('SELECT id, nom, updated_at FROM pieces WHERE actif = 1 ORDER BY id') as $p) {
    $modif = $p['updated_at'] ? date('Y-m-d', strtotime($p['updated_at'])) : null;
    $ajouter(lien_piece($p), '0.6', 'weekly', $modif);
}

header('Content-Type: application/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($adresses as $a) {
    echo "  <url>\n    <loc>" . e($a['loc']) . "</loc>\n";
    if ($a['lastmod']) {
        echo '    <lastmod>' . $a['lastmod'] . "</lastmod>\n";
    }
    echo '    <changefreq>' . $a['changefreq'] . "</changefreq>\n    <priority>" . $a['priority'] . "</priority>\n  </url>\n";
}
echo "</urlset>\n";
