<?php
require __DIR__ . '/../includes/bootstrap.php';
exiger_admin('pieces');

$id = (int) ($_GET['id'] ?? 0);
$piece = $id ? get_piece($id, false) : null;
if ($id && !$piece) {
    flash('Pièce introuvable.', 'erreur');
    redirect('admin/pieces.php');
}

$vide = [
    'reference' => '', 'nom' => '', 'description' => '', 'marque' => MARQUES[0], 'modeles' => '',
    'categorie' => CATEGORIES[0], 'prix' => 0, 'stock' => 0, 'image' => null,
    'promo_type' => '', 'promo_valeur' => '', 'promo_debut' => '', 'promo_fin' => '',
    'nouveau' => 1, 'actif' => 1,
];
$f = $piece ? array_merge($vide, $piece) : $vide;
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach (['nom', 'description', 'marque', 'modeles', 'categorie', 'promo_type', 'promo_debut', 'promo_fin'] as $k) {
        $f[$k] = trim((string) ($_POST[$k] ?? ''));
    }
    // Référence, prix et stock ne sont plus saisis : on garde les valeurs existantes
    $f['promo_valeur'] = (int) preg_replace('/\D/', '', (string) ($_POST['promo_valeur'] ?? ''));
    if (!peut('promotions')) {
        // sans l'autorisation Promotions, la promotion existante est conservée telle quelle
        foreach (['promo_type', 'promo_valeur', 'promo_debut', 'promo_fin'] as $k) {
            $f[$k] = $piece[$k] ?? '';
        }
        $f['promo_type'] = (string) $f['promo_type'];
    }
    $f['nouveau'] = isset($_POST['nouveau']) ? 1 : 0;
    $f['actif'] = isset($_POST['actif']) ? 1 : 0;

    if ($f['nom'] === '') $erreurs[] = 'Le nom de la pièce est obligatoire.';
    if (!in_array($f['promo_type'], ['', 'pourcent'], true)) $f['promo_type'] = '';

    if ($f['promo_type'] === '') {
        $f['promo_valeur'] = null;
        $f['promo_debut'] = $f['promo_fin'] = '';
    } else {
        if ($f['promo_type'] === 'pourcent' && ($f['promo_valeur'] < 1 || $f['promo_valeur'] > 90)) {
            $erreurs[] = 'La remise doit être entre 1 et 90 %.';
        }
        if ($f['promo_debut'] && $f['promo_fin'] && $f['promo_fin'] < $f['promo_debut']) {
            $erreurs[] = 'La date de fin de promotion est avant la date de début.';
        }
    }

    $nouvelleImage = null;
    if (!$erreurs) {
        try {
            $nouvelleImage = enregistrer_image($_FILES['image'] ?? []);
        } catch (RuntimeException $ex) {
            $erreurs[] = $ex->getMessage();
        }
    }

    if (!$erreurs) {
        $ancienneImage = $f['image'];
        if ($nouvelleImage) {
            $f['image'] = $nouvelleImage;
        } elseif (isset($_POST['retirer_image'])) {
            $f['image'] = null;
        }
        $valeurs = [
            $f['reference'], $f['nom'], $f['description'], $f['marque'], $f['modeles'], $f['categorie'],
            $f['prix'], $f['stock'], $f['image'], $f['promo_type'] ?: null, $f['promo_valeur'] ?: null,
            $f['promo_debut'] ?: null, $f['promo_fin'] ?: null, $f['nouveau'], $f['actif'],
        ];
        if ($piece) {
            $valeurs[] = $piece['id'];
            db()->prepare("UPDATE pieces SET reference=?, nom=?, description=?, marque=?, modeles=?, categorie=?,
                prix=?, stock=?, image=?, promo_type=?, promo_valeur=?, promo_debut=?, promo_fin=?, nouveau=?, actif=?,
                updated_at = " . sql_maintenant() . " WHERE id = ?")->execute($valeurs);
            if ($ancienneImage !== $f['image']) {
                supprimer_image($ancienneImage);
            }
            flash('Pièce mise à jour.');
        } else {
            db()->prepare('INSERT INTO pieces (reference, nom, description, marque, modeles, categorie, prix, stock, image,
                promo_type, promo_valeur, promo_debut, promo_fin, nouveau, actif) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute($valeurs);
            flash('Pièce ajoutée au catalogue.');
        }
        redirect('admin/pieces.php');
    }
}

$titre = $piece ? 'Modifier : ' . $piece['nom'] : 'Ajouter une pièce';
$page = 'pieces';
$actionEntete = $piece ? '<a href="' . lien_piece($piece) . '" class="btn btn-contour" target="_blank">Voir sur le site ↗</a>' : '';
require __DIR__ . '/_header.php';
?>

<?php if ($erreurs): ?>
    <div class="alerte alerte-erreur"><?= implode('<br>', array_map('e', $erreurs)) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="formulaire form-piece">
    <?= csrf_field() ?>
    <div class="form-piece-grille">
        <section class="panneau">
            <h2>Informations</h2>
            <div class="champs-2">
                <label>Nom de la pièce *<input type="text" name="nom" value="<?= e($f['nom']) ?>" required placeholder="Ex. Plaquettes de frein avant"></label>
                <label>Marque
                    <select name="marque">
                        <?php foreach (MARQUES as $m): ?><option <?= $f['marque'] === $m ? 'selected' : '' ?>><?= e($m) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <label>Catégorie
                    <select name="categorie">
                        <?php foreach (CATEGORIES as $c): ?><option <?= $f['categorie'] === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
                    </select>
                </label>
            </div>
            <label>Modèles compatibles<input type="text" name="modeles" value="<?= e($f['modeles']) ?>" placeholder="Ex. Sorento, Sportage 2010-2015"></label>
            <label>Description<textarea name="description" rows="5"><?= e($f['description']) ?></textarea></label>
            <label class="case"><input type="checkbox" name="nouveau" <?= $f['nouveau'] ? 'checked' : '' ?>> Afficher dans « Nouveaux arrivages »</label>
            <label class="case"><input type="checkbox" name="actif" <?= $f['actif'] ? 'checked' : '' ?>> Visible sur le site</label>
        </section>

        <div>
            <section class="panneau">
                <h2>Photo</h2>
                <img src="<?= e(piece_image($f)) ?>" alt="" class="apercu-image" id="apercu">
                <label>Choisir une photo (JPG, PNG, WEBP · 5 Mo max)
                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
                           onchange="if(this.files[0]) document.getElementById('apercu').src = window.URL.createObjectURL(this.files[0])">
                </label>
                <?php if ($f['image']): ?>
                    <label class="case"><input type="checkbox" name="retirer_image"> Retirer la photo actuelle</label>
                <?php endif; ?>
            </section>

            <?php if (peut('promotions')): ?>
            <section class="panneau panneau-promo">
                <h2>Promotion</h2>
                <label>Type de promotion
                    <select name="promo_type" data-promo-type="bloc-promo">
                        <option value="" <?= !$f['promo_type'] ? 'selected' : '' ?>>Aucune promotion</option>
                        <option value="pourcent" <?= $f['promo_type'] === 'pourcent' ? 'selected' : '' ?>>Remise en pourcentage</option>
                    </select>
                </label>
                <div id="bloc-promo">
                    <label>Remise (%)<input type="number" name="promo_valeur" value="<?= e((string) $f['promo_valeur']) ?>" min="1" max="90"></label>
                    <div class="champs-2">
                        <label>Début<input type="date" name="promo_debut" value="<?= e((string) $f['promo_debut']) ?>"></label>
                        <label>Fin<input type="date" name="promo_fin" value="<?= e((string) $f['promo_fin']) ?>"></label>
                    </div>
                    <p class="petit">Laissez les dates vides pour une promotion sans limite.</p>
                </div>
            </section>
            <?php elseif ($f['promo_type']): ?>
            <section class="panneau panneau-promo">
                <h2>Promotion</h2>
                <p class="petit">Cette pièce est en promotion. Seul un compte avec l’accès « Promotions » peut la modifier.</p>
            </section>
            <?php endif; ?>
        </div>
    </div>

    <div class="barre-actions">
        <a href="<?= url('admin/pieces.php') ?>" class="btn btn-contour">Annuler</a>
        <button type="submit" class="btn btn-rouge"><?= $piece ? 'Enregistrer les modifications' : 'Ajouter la pièce' ?></button>
    </div>
</form>

<?php require __DIR__ . '/_footer.php'; ?>
