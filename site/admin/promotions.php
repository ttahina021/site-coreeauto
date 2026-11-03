<?php
require __DIR__ . '/../includes/bootstrap.php';
exiger_admin('promotions');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'appliquer') {
        $type = $_POST['promo_type'] ?? '';
        $valeur = (int) ($_POST['promo_valeur'] ?? 0);
        $debut = $_POST['promo_debut'] ?: null;
        $fin = $_POST['promo_fin'] ?: null;
        $marque = $_POST['marque'] ?? '';
        $categorie = $_POST['categorie'] ?? '';

        if ($type !== 'pourcent' || $valeur < 1 || $valeur > 90) {
            flash('Indiquez une remise entre 1 et 90 %.', 'erreur');
        } elseif ($debut && $fin && $fin < $debut) {
            flash('La date de fin est avant la date de début.', 'erreur');
        } else {
            $where = ['actif = 1'];
            $params = [$type, $valeur, $debut, $fin];
            if ($marque !== '') { $where[] = 'marque = ?'; $params[] = $marque; }
            if ($categorie !== '') { $where[] = 'categorie = ?'; $params[] = $categorie; }
            $stmt = db()->prepare("UPDATE pieces SET promo_type = ?, promo_valeur = ?, promo_debut = ?, promo_fin = ?,
                updated_at = " . sql_maintenant() . " WHERE " . implode(' AND ', $where));
            $stmt->execute($params);
            flash('Promotion de ' . $valeur . ' % appliquée à ' . $stmt->rowCount() . ' pièce(s).');
        }
    }

    if ($action === 'visibilite' && est_super_admin()) {
        $mode = $_POST['page_promotions'] ?? 'auto';
        if (isset(MODES_PAGE_PROMOS[$mode])) {
            enregistrer_reglage('page_promotions', $mode);
            $visible = $mode === 'afficher' || ($mode === 'auto' && nb_promos_actives() > 0);
            flash('Page Promotions : ' . ($visible ? 'visible' : 'masquée') . ' sur le site.');
        }
    }

    if ($action === 'retirer') {
        $ids = array_map('intval', (array) ($_POST['ids'] ?? []));
        if ($ids) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            db()->prepare("UPDATE pieces SET promo_type = NULL, promo_valeur = NULL, promo_debut = NULL, promo_fin = NULL,
                updated_at = " . sql_maintenant() . " WHERE id IN ($in)")->execute($ids);
            flash('Promotion retirée de ' . count($ids) . ' pièce(s).');
        } else {
            flash('Cochez au moins une pièce.', 'erreur');
        }
    }
    redirect('admin/promotions.php');
}

$promos = db()->query('SELECT * FROM pieces WHERE promo_type IS NOT NULL ORDER BY promo_fin IS NULL, promo_fin ASC')->fetchAll();
$today = date('Y-m-d');
function statut_promo(array $p, string $today): array
{
    if (!empty($p['promo_fin']) && $p['promo_fin'] < $today) return ['Expirée', ''];
    if (!empty($p['promo_debut']) && $p['promo_debut'] > $today) return ['Programmée', 'etiquette-bleu'];
    return ['Active', 'etiquette-vert'];
}

$titre = 'Promotions';
$page = 'promotions';
require __DIR__ . '/_header.php';
?>

<?php if (est_super_admin()): ?>
<?php $modePromos = reglage('page_promotions', 'auto'); ?>
<section class="panneau">
    <h2>Page Promotions sur le site</h2>
    <p class="petit">
        Actuellement <strong><?= promotions_visibles() ? 'visible' : 'masquée' ?></strong>
        (<?= nb_promos_actives() ?> pièce(s) en promotion aujourd’hui).
        Quand elle est masquée, le lien « Promotions » disparaît du menu et de l’accueil.
    </p>
    <form method="post" class="formulaire">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="visibilite">
        <?php foreach (MODES_PAGE_PROMOS as $val => $lib): ?>
            <label class="case"><input type="radio" name="page_promotions" value="<?= $val ?>" <?= $modePromos === $val ? 'checked' : '' ?>> <?= e($lib) ?></label>
        <?php endforeach; ?>
        <button type="submit" class="btn btn-bleu">Enregistrer</button>
    </form>
</section>
<?php endif; ?>

<section class="panneau panneau-promo">
    <h2>Appliquer une promotion à plusieurs pièces</h2>
    <p class="petit">Pour une promotion sur une seule pièce, modifiez directement la pièce. Sur le site, la remise s’affiche en badge « -X % » (les prix ne sont pas affichés).</p>
    <form method="post" class="formulaire promo-groupee">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="appliquer">
        <input type="hidden" name="promo_type" value="pourcent">
        <label>Marque
            <select name="marque">
                <option value="">Toutes les marques</option>
                <?php foreach (MARQUES as $m): ?><option><?= e($m) ?></option><?php endforeach; ?>
            </select>
        </label>
        <label>Catégorie
            <select name="categorie">
                <option value="">Toutes les catégories</option>
                <?php foreach (CATEGORIES as $c): ?><option><?= e($c) ?></option><?php endforeach; ?>
            </select>
        </label>
        <label>Remise (%)<input type="number" name="promo_valeur" min="1" max="90" value="10" required></label>
        <label>Début<input type="date" name="promo_debut" value="<?= $today ?>"></label>
        <label>Fin<input type="date" name="promo_fin" value="<?= date('Y-m-d', strtotime('+15 days')) ?>"></label>
        <button type="submit" class="btn btn-rouge" data-confirmer="Appliquer cette remise à toutes les pièces correspondantes ? Les promotions existantes sur ces pièces seront remplacées.">Appliquer</button>
    </form>
</section>

<section class="panneau">
    <h2>Pièces en promotion</h2>
    <?php if (!$promos): ?>
        <p class="petit">Aucune promotion pour l’instant.</p>
    <?php else: ?>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="retirer">
            <div class="tableau-defilant">
            <table class="tableau tableau-admin">
                <thead>
                    <tr><th><input type="checkbox" id="tout-cocher" aria-label="Tout cocher"></th><th>Pièce</th><th>Remise</th><th>Période</th><th>Statut</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($promos as $p): [$statut, $classe] = statut_promo($p, $today); ?>
                    <tr>
                        <td><input type="checkbox" name="ids[]" value="<?= $p['id'] ?>"></td>
                        <td><strong><?= e($p['nom']) ?></strong><br><small><?= e($p['marque']) ?> · <?= e($p['categorie']) ?></small></td>
                        <td><span class="etiquette etiquette-rouge"><?= $p['promo_type'] === 'pourcent' ? '-' . (int) $p['promo_valeur'] . ' %' : 'Prix fixe (inactif)' ?></span></td>
                        <td>
                            <?= $p['promo_debut'] ? date('d/m/Y', strtotime($p['promo_debut'])) : '…' ?>
                            → <?= $p['promo_fin'] ? date('d/m/Y', strtotime($p['promo_fin'])) : 'sans limite' ?>
                        </td>
                        <td><span class="etiquette <?= $classe ?>"><?= $statut ?></span></td>
                        <td><a class="btn-mini" href="<?= url('admin/piece.php?id=' . $p['id']) ?>">Modifier</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <button type="submit" class="btn btn-contour" data-confirmer="Retirer la promotion des pièces cochées ?">Retirer la promotion des pièces cochées</button>
        </form>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/_footer.php'; ?>
