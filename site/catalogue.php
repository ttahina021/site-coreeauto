<?php
require_once __DIR__ . '/includes/bootstrap.php';

$q = trim($_GET['q'] ?? '');
$marque = $_GET['marque'] ?? '';
$categorie = $_GET['categorie'] ?? '';
$promo = !empty($_GET['promo']);
$nouveau = !empty($_GET['nouveau']);
$tri = $_GET['tri'] ?? 'recent';

$where = ['actif = 1'];
$params = [];
if ($q !== '') {
    $where[] = '(nom LIKE :q OR reference LIKE :q OR modeles LIKE :q OR description LIKE :q OR marque LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
if (in_array($marque, MARQUES, true)) {
    $where[] = 'marque = :marque';
    $params[':marque'] = $marque;
}
if (in_array($categorie, CATEGORIES, true)) {
    $where[] = 'categorie = :categorie';
    $params[':categorie'] = $categorie;
}
if ($promo) {
    $where[] = promo_active_sql();
}
if ($nouveau) {
    $where[] = 'nouveau = 1';
}
$ordres = [
    'recent' => 'created_at DESC, id DESC',
    'prix_asc' => 'prix ASC',
    'prix_desc' => 'prix DESC',
    'nom' => 'LOWER(nom) ASC',
];
$order = $ordres[$tri] ?? $ordres['recent'];

$stmt = db()->prepare('SELECT * FROM pieces WHERE ' . implode(' AND ', $where) . ' ORDER BY ' . $order);
$stmt->execute($params);
$pieces = $stmt->fetchAll();

// Titre, texte d'en-tête et balises SEO selon le filtre
if ($promo && !$marque && !$categorie) {
    $titre = 'Promotions sur les pièces détachées coréennes';
    $h1 = 'Promotions en cours';
} elseif ($nouveau && !$marque && !$categorie) {
    $titre = 'Nouveaux arrivages de pièces coréennes';
    $h1 = 'Nouveaux arrivages';
} elseif ($marque && !$categorie) {
    $titre = 'Pièces détachées ' . $marque;
    $h1 = 'Pièces détachées ' . $marque;
} elseif ($categorie && !$marque) {
    $titre = $categorie . ' : pièces pour véhicules coréens';
    $h1 = $categorie . ' : pièces pour véhicules coréens';
} elseif ($marque && $categorie) {
    $titre = $categorie . ' ' . $marque;
    $h1 = $categorie . ' pour ' . $marque;
} else {
    $titre = 'Catalogue des pièces détachées coréennes';
    $h1 = 'Catalogue des pièces';
}
$page = $promo ? 'promos' : 'catalogue';
$filtres = ['marque' => $marque, 'categorie' => $categorie, 'promo' => $promo ? '1' : '', 'nouveau' => $nouveau ? '1' : ''];
$canonical = lien_catalogue($filtres);
$description = $titre . ' chez Corée Auto : ' . count($pieces) . ' pièce(s) d’origine coréenne disponibles, prix en Ariary, retrait dans nos magasins d’Antananarivo et Tamatave.';
if ($q !== '' || $tri !== 'recent') {
    $robots = 'noindex, follow'; // résultats de recherche et tris : pas d'indexation
}
require __DIR__ . '/includes/header.php';
?>

<section class="page-titre">
    <div class="conteneur">
        <h1><?= e($h1) ?></h1>
        <p><?= count($pieces) ?> pièce(s)<?= $q !== '' ? ' pour « ' . e($q) . ' »' : '' ?></p>
    </div>
</section>

<div class="conteneur catalogue">
    <aside class="filtres">
        <form method="get" action="<?= url('catalogue') ?>" id="form-filtres">
            <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= e($q) ?>"><?php endif; ?>
            <h3>Marque</h3>
            <label class="radio"><input type="radio" name="marque" value="" <?= $marque === '' ? 'checked' : '' ?>> Toutes</label>
            <?php foreach (MARQUES as $m): ?>
                <label class="radio"><input type="radio" name="marque" value="<?= e($m) ?>" <?= $marque === $m ? 'checked' : '' ?>> <?= e($m) ?></label>
            <?php endforeach; ?>

            <h3>Catégorie</h3>
            <select name="categorie">
                <option value="">Toutes les catégories</option>
                <?php foreach (CATEGORIES as $c): ?>
                    <option value="<?= e($c) ?>" <?= $categorie === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                <?php endforeach; ?>
            </select>

            <h3>Offres</h3>
            <?php if (promotions_visibles() || $promo): ?>
            <label class="radio"><input type="checkbox" name="promo" value="1" <?= $promo ? 'checked' : '' ?>> En promotion</label>
            <?php endif; ?>
            <label class="radio"><input type="checkbox" name="nouveau" value="1" <?= $nouveau ? 'checked' : '' ?>> Nouveaux arrivages</label>

            <h3>Trier par</h3>
            <select name="tri">
                <option value="recent" <?= $tri === 'recent' ? 'selected' : '' ?>>Plus récentes</option>
                <option value="prix_asc" <?= $tri === 'prix_asc' ? 'selected' : '' ?>>Prix croissant</option>
                <option value="prix_desc" <?= $tri === 'prix_desc' ? 'selected' : '' ?>>Prix décroissant</option>
                <option value="nom" <?= $tri === 'nom' ? 'selected' : '' ?>>Nom (A-Z)</option>
            </select>

            <button type="submit" class="btn btn-bleu btn-bloc">Filtrer</button>
            <a href="<?= url('catalogue') ?>" class="lien-reinit">Réinitialiser les filtres</a>
        </form>
    </aside>

    <div class="resultats">
        <?php if ($pieces): ?>
            <div class="grille-cartes grille-3">
                <?php foreach ($pieces as $p) { require __DIR__ . '/includes/carte-piece.php'; } ?>
            </div>
        <?php else: ?>
            <div class="vide">
                <h2>Aucune pièce trouvée</h2>
                <p>Essayez une autre recherche, ou contactez-nous : nous trouvons la pièce pour vous.</p>
                <a href="<?= url('magasins') ?>" class="btn btn-rouge">Nous contacter</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
