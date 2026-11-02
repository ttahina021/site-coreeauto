<?php
// Routeur des adresses propres : coreeauto.mg/atelier, /piece/12-nom, /marque/kia…
// Apache y envoie les adresses qui ne sont pas des fichiers (voir .htaccess).
// En local : php -S localhost:8000 router.php

if (PHP_SAPI === 'cli-server') {
    define('BASE_URL', '');
} else {
    define('BASE_URL', rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/'));
}

$chemin = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
if (BASE_URL !== '' && str_starts_with($chemin, BASE_URL)) {
    $chemin = substr($chemin, strlen(BASE_URL));
}
$chemin = '/' . trim($chemin, '/');

$pagesPubliques = ['index', 'catalogue', 'piece', 'panier', 'atelier', 'contact'];

// Serveur PHP intégré : les vrais fichiers (images, CSS, admin…) sont servis tels quels
if (PHP_SAPI === 'cli-server' && $chemin !== '/' && is_file(__DIR__ . $chemin)
    && !preg_match('#^/(' . implode('|', $pagesPubliques) . ')\.php$#', $chemin)) {
    return false;
}
// Serveur PHP intégré : dossier avec index.php (ex. /admin/)
if (PHP_SAPI === 'cli-server' && $chemin !== '/' && is_file(__DIR__ . $chemin . '/index.php')) {
    $_SERVER['SCRIPT_NAME'] = $chemin . '/index.php';
    chdir(__DIR__ . $chemin);
    require __DIR__ . $chemin . '/index.php';
    return;
}

require_once __DIR__ . '/includes/bootstrap.php';

function redirection_301(string $cible): never
{
    header('Location: ' . $cible, true, 301);
    exit;
}

function page(string $fichier): never
{
    require __DIR__ . '/' . $fichier;
    exit;
}

$get = $_SERVER['REQUEST_METHOD'] === 'GET';

// Anciennes adresses en .php → redirection définitive vers l'adresse propre
if (preg_match('#^/(' . implode('|', $pagesPubliques) . ')\.php$#', $chemin, $m)) {
    if ($get) {
        $q = $_GET;
        $cible = match ($m[1]) {
            'index' => url(''),
            'catalogue' => lien_catalogue($q),
            'piece' => ($p = get_piece((int) ($q['id'] ?? 0))) ? lien_piece($p) : url('catalogue'),
            'panier' => url('panier' . (isset($q['confirmation']) ? '?confirmation=' . (int) $q['confirmation'] : '')),
            'atelier' => url('atelier'),
            'contact' => url('magasins'),
        };
        redirection_301($cible);
    }
    page($m[1] . '.php');
}

switch (true) {
    case $chemin === '/':
        page('index.php');

    case $chemin === '/catalogue':
        // Filtres simples → adresse courte (/marque/kia, /promotions…)
        $court = lien_catalogue($_GET);
        if ($get && $_GET && !str_starts_with($court, url('catalogue'))) {
            redirection_301($court);
        }
        page('catalogue.php');

    case $chemin === '/promotions':
        if (!promotions_visibles()) {
            // Page masquée par l'admin : redirection temporaire vers le catalogue
            header('Location: ' . url('catalogue'), true, 302);
            exit;
        }
        $_GET['promo'] = '1';
        page('catalogue.php');

    case $chemin === '/arrivages':
        $_GET['nouveau'] = '1';
        page('catalogue.php');

    case (bool) preg_match('#^/marque/([a-z0-9-]+)$#', $chemin, $m):
        if ($marque = depuis_slug($m[1], MARQUES)) {
            $_GET['marque'] = $marque;
            page('catalogue.php');
        }
        break;

    case (bool) preg_match('#^/categorie/([a-z0-9-]+)$#', $chemin, $m):
        if ($categorie = depuis_slug($m[1], CATEGORIES)) {
            $_GET['categorie'] = $categorie;
            page('catalogue.php');
        }
        break;

    case (bool) preg_match('#^/piece/(\d+)(?:-[a-z0-9-]*)?$#', $chemin, $m):
        if ($p = get_piece((int) $m[1])) {
            if ($get && url(ltrim($chemin, '/')) !== lien_piece($p)) {
                redirection_301(lien_piece($p)); // nom modifié : on corrige l'adresse
            }
            $_GET['id'] = $p['id'];
            page('piece.php');
        }
        break;

    case $chemin === '/panier':
        page('panier.php');

    case $chemin === '/atelier':
        page('atelier.php');

    case $chemin === '/magasins':
        page('contact.php');

    case $chemin === '/sitemap.xml':
        page('sitemap.php');

    case $chemin === '/robots.txt':
        header('Content-Type: text/plain; charset=UTF-8');
        echo "User-agent: *\nDisallow: /admin/\nDisallow: /panier\nDisallow: /catalogue?\n\nSitemap: " . url_absolue(url('sitemap.xml')) . "\n";
        exit;
}

// Adresse inconnue
http_response_code(404);
$titre = 'Page introuvable';
$robots = 'noindex';
require __DIR__ . '/includes/header.php';
?>
<section class="conteneur vide">
    <h1>Cette page n’existe pas</h1>
    <p>Elle a peut-être été déplacée. Cherchez votre pièce dans le catalogue.</p>
    <a class="btn btn-bleu" href="<?= url('catalogue') ?>">Voir le catalogue</a>
    <a class="btn btn-contour" href="<?= url('') ?>">Accueil</a>
</section>
<?php require __DIR__ . '/includes/footer.php';
