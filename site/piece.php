<?php
require_once __DIR__ . '/includes/bootstrap.php';

$p = get_piece((int) ($_GET['id'] ?? 0));
if (!$p) {
    http_response_code(404);
    $titre = 'Pièce introuvable';
    $robots = 'noindex';
    require __DIR__ . '/includes/header.php';
    echo '<div class="conteneur vide"><h2>Cette pièce n’existe pas ou n’est plus disponible.</h2><a class="btn btn-bleu" href="' . url('catalogue') . '">Retour au catalogue</a></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$enPromo = promo_active($p);
$similaires = db()->prepare('SELECT * FROM pieces WHERE actif = 1 AND id != ? AND (categorie = ? OR marque = ?) ORDER BY (categorie = ?) DESC, ' . (DB_DRIVER === 'mysql' ? 'RAND()' : 'RANDOM()') . ' LIMIT 4');
$similaires->execute([$p['id'], $p['categorie'], $p['marque'], $p['categorie']]);
$similaires = $similaires->fetchAll();

$messageWa = 'Bonjour Corée Auto, je suis intéressé(e) par : ' . $p['nom'] . ' ' . $p['marque'] . ($p['modeles'] ? ' (' . $p['modeles'] . ')' : '');

$titre = $p['nom'] . ' ' . $p['marque'] . ($p['modeles'] ? ' ' . $p['modeles'] : '');
$page = 'catalogue';
$canonical = lien_piece($p);
$description = $p['nom'] . ' ' . $p['marque']
    . ($p['modeles'] ? ', compatible ' . $p['modeles'] : '')
    . '. ' . ($p['description'] ?: 'Pièce d’origine coréenne') . ' Disponible chez Corée Auto, Madagascar.';
$ogImage = piece_image($p);
$jsonLd = [
    [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $p['nom'],
        'brand' => ['@type' => 'Brand', 'name' => $p['marque']],
        'category' => $p['categorie'],
        'description' => $p['description'] ?: $p['nom'],
        'image' => url_absolue(piece_image($p)),
    ],
    [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil', 'item' => url_absolue(url(''))],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $p['categorie'], 'item' => url_absolue(lien_categorie($p['categorie']))],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $p['nom'], 'item' => url_absolue(lien_piece($p))],
        ],
    ],
];
require __DIR__ . '/includes/header.php';
?>

<div class="conteneur fil-ariane">
    <a href="<?= url('') ?>">Accueil</a> /
    <a href="<?= lien_categorie($p['categorie']) ?>"><?= e($p['categorie']) ?></a> /
    <span><?= e($p['nom']) ?></span>
</div>

<section class="conteneur fiche">
    <div class="fiche-image">
        <img src="<?= e(piece_image($p)) ?>" alt="<?= e($p['nom']) ?>">
        <?php if ($enPromo): ?><span class="badge badge-promo badge-grand">-<?= remise_pct($p) ?> %</span><?php endif; ?>
    </div>
    <div class="fiche-infos">
        <div class="carte-meta"><?= e($p['marque']) ?> · <?= e($p['categorie']) ?></div>
        <h1><?= e($p['nom']) ?></h1>
        <?php if ($p['modeles']): ?>
            <p class="fiche-modeles">Compatible : <strong><?= e($p['modeles']) ?></strong></p>
        <?php endif; ?>

        <?php if ($enPromo): ?>
            <p class="fiche-promo"><span class="badge badge-promo">-<?= remise_pct($p) ?> %</span>
                <?php if ($p['promo_fin']): ?><span class="promo-fin">Offre valable jusqu’au <?= e(date('d/m/Y', strtotime($p['promo_fin']))) ?></span><?php endif; ?>
            </p>
        <?php endif; ?>

            <form action="<?= url('panier') ?>" method="post" class="fiche-ajout">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="ajouter">
                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                <label>Quantité <input type="number" name="quantite" value="1" min="1" max="99"></label>
                <button type="submit" class="btn btn-rouge">Ajouter au panier</button>
            </form>
        <a class="btn btn-whatsapp" href="https://wa.me/<?= e(WHATSAPP) ?>?text=<?= rawurlencode($messageWa) ?>" target="_blank" rel="noopener">Demander sur WhatsApp</a>

        <?php if ($p['description']): ?>
            <div class="fiche-description">
                <h2>Description</h2>
                <p><?= nl2br(e($p['description'])) ?></p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($similaires): ?>
<section class="section section-grise">
    <div class="conteneur">
        <div class="section-entete"><h2><span class="trait-bleu"></span>Pièces similaires</h2></div>
        <div class="grille-cartes">
            <?php foreach ($similaires as $p) { require __DIR__ . '/includes/carte-piece.php'; } ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
