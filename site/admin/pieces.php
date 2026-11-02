<?php
require __DIR__ . '/../includes/bootstrap.php';
exiger_admin('pieces');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'supprimer' && est_super_admin() && ($p = get_piece($id, false))) {
        db()->prepare('DELETE FROM pieces WHERE id = ?')->execute([$id]);
        supprimer_image($p['image']);
        flash('Pièce « ' . $p['nom'] . ' » supprimée.');
    } elseif ($action === 'basculer') {
        db()->prepare("UPDATE pieces SET actif = 1 - actif, updated_at = " . sql_maintenant() . " WHERE id = ?")->execute([$id]);
        flash('Visibilité mise à jour.');
    }
    redirect('admin/pieces.php?' . http_build_query(array_intersect_key($_GET, ['q' => 1, 'marque' => 1, 'categorie' => 1])));
}

$q = trim($_GET['q'] ?? '');
$marque = $_GET['marque'] ?? '';
$categorie = $_GET['categorie'] ?? '';
$where = ['1'];
$params = [];
if ($q !== '') {
    $where[] = '(nom LIKE :q OR reference LIKE :q OR modeles LIKE :q)';
    $params[':q'] = "%$q%";
}
if ($marque !== '') {
    $where[] = 'marque = :m';
    $params[':m'] = $marque;
}
if ($categorie !== '') {
    $where[] = 'categorie = :c';
    $params[':c'] = $categorie;
}
$stmt = db()->prepare('SELECT * FROM pieces WHERE ' . implode(' AND ', $where) . ' ORDER BY id DESC');
$stmt->execute($params);
$pieces = $stmt->fetchAll();

$titre = 'Pièces';
$page = 'pieces';
$actionEntete = '<a href="' . url('admin/piece.php') . '" class="btn btn-rouge">+ Ajouter une pièce</a>';
require __DIR__ . '/_header.php';
?>

<form class="barre-filtres" method="get">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Nom, référence, modèle…">
    <select name="marque">
        <option value="">Toutes les marques</option>
        <?php foreach (MARQUES as $m): ?><option <?= $marque === $m ? 'selected' : '' ?>><?= e($m) ?></option><?php endforeach; ?>
    </select>
    <select name="categorie">
        <option value="">Toutes les catégories</option>
        <?php foreach (CATEGORIES as $c): ?><option <?= $categorie === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
    </select>
    <button class="btn btn-bleu">Filtrer</button>
</form>

<section class="panneau">
    <p class="petit"><?= count($pieces) ?> pièce(s)</p>
    <div class="tableau-defilant">
    <table class="tableau tableau-admin">
        <thead>
            <tr><th></th><th>Pièce</th><th>Marque / catégorie</th><th>Prix</th><th>Stock</th><th>Statut</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($pieces as $p): ?>
            <tr class="<?= $p['actif'] ? '' : 'inactif' ?>">
                <td><img src="<?= e(piece_image($p)) ?>" alt="" class="miniature"></td>
                <td>
                    <a href="<?= url('admin/piece.php?id=' . $p['id']) ?>"><strong><?= e($p['nom']) ?></strong></a><br>
                    <small><?= e($p['reference']) ?></small>
                </td>
                <td><?= e($p['marque']) ?><br><small><?= e($p['categorie']) ?></small></td>
                <td>
                    <?php if (promo_active($p)): ?>
                        <span class="prix-ancien"><?= prix((int) $p['prix']) ?></span><br>
                        <strong class="texte-rouge"><?= prix(prix_final($p)) ?></strong>
                        <span class="etiquette etiquette-rouge">-<?= remise_pct($p) ?> %</span>
                    <?php else: ?>
                        <?= prix((int) $p['prix']) ?>
                    <?php endif; ?>
                </td>
                <td><strong class="<?= $p['stock'] <= 0 ? 'texte-rouge' : '' ?>"><?= (int) $p['stock'] ?></strong></td>
                <td>
                    <?= $p['actif'] ? '<span class="etiquette etiquette-vert">En ligne</span>' : '<span class="etiquette">Masquée</span>' ?>
                    <?= $p['nouveau'] ? '<span class="etiquette etiquette-bleu">Arrivage</span>' : '' ?>
                </td>
                <td class="actions">
                    <a href="<?= url('admin/piece.php?id=' . $p['id']) ?>" class="btn-mini">Modifier</a>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button name="action" value="basculer" class="btn-mini"><?= $p['actif'] ? 'Masquer' : 'Publier' ?></button>
                        <?php if (est_super_admin()): ?>
                        <button name="action" value="supprimer" class="btn-mini btn-mini-rouge" data-confirmer="Supprimer définitivement cette pièce ?">Supprimer</button>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</section>

<?php require __DIR__ . '/_footer.php'; ?>
