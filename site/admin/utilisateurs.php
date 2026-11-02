<?php
// Gestion des comptes de l'administration (réservée à l'administrateur principal).
require __DIR__ . '/../includes/bootstrap.php';
exiger_admin('super');

$moi = admin_courant();
$id = (int) ($_GET['id'] ?? 0);
$edite = null;
if ($id) {
    $stmt = db()->prepare('SELECT * FROM admins WHERE id = ?');
    $stmt->execute([$id]);
    $edite = $stmt->fetch() ?: null;
    if (!$edite) {
        flash('Utilisateur introuvable.', 'erreur');
        redirect('admin/utilisateurs.php');
    }
}

$f = $edite
    ? ['identifiant' => $edite['identifiant'], 'nom' => $edite['nom'], 'role' => $edite['role'],
       'permissions' => permissions_admin($edite), 'actif' => (int) $edite['actif']]
    : ['identifiant' => '', 'nom' => '', 'role' => 'employe', 'permissions' => ['pieces', 'commandes', 'atelier'], 'actif' => 1];
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $f['identifiant'] = trim((string) ($_POST['identifiant'] ?? ''));
    $f['nom'] = trim((string) ($_POST['nom'] ?? ''));
    $f['role'] = ($_POST['role'] ?? '') === 'super' ? 'super' : 'employe';
    $f['permissions'] = array_values(array_intersect(array_keys(PERMISSIONS_ADMIN), (array) ($_POST['permissions'] ?? [])));
    $f['actif'] = $edite ? (isset($_POST['actif']) ? 1 : 0) : 1;
    $mdp = (string) ($_POST['mot_de_passe'] ?? '');

    if (!preg_match('/^[A-Za-z0-9._@-]{3,50}$/', $f['identifiant'])) {
        $erreurs[] = 'L’identifiant doit faire de 3 à 50 caractères (lettres, chiffres, point, tiret, @), sans espace.';
    } else {
        $stmt = db()->prepare('SELECT COUNT(*) FROM admins WHERE identifiant = ? AND id <> ?');
        $stmt->execute([$f['identifiant'], $edite['id'] ?? 0]);
        if ((int) $stmt->fetchColumn() > 0) {
            $erreurs[] = 'Cet identifiant est déjà utilisé.';
        }
    }
    if (mb_strlen($f['nom']) > 100) {
        $erreurs[] = 'Le nom est trop long.';
    }
    if ((!$edite || $mdp !== '') && strlen($mdp) < 8) {
        $erreurs[] = 'Le mot de passe doit faire au moins 8 caractères.';
    }
    if ($f['role'] === 'employe' && !$f['permissions']) {
        $erreurs[] = 'Cochez au moins un accès pour ce compte.';
    }
    if ($edite && (int) $edite['id'] === (int) $moi['id'] && ($f['role'] !== 'super' || !$f['actif'])) {
        $erreurs[] = 'Vous ne pouvez pas retirer vos propres droits ni désactiver votre propre compte.';
    }
    if ($edite && $edite['role'] === 'super' && (int) $edite['actif'] === 1 && ($f['role'] !== 'super' || !$f['actif'])) {
        $stmt = db()->prepare("SELECT COUNT(*) FROM admins WHERE role = 'super' AND actif = 1 AND id <> ?");
        $stmt->execute([$edite['id']]);
        if ((int) $stmt->fetchColumn() === 0) {
            $erreurs[] = 'Il doit toujours rester au moins un administrateur principal actif.';
        }
    }

    if (!$erreurs) {
        $permissions = $f['role'] === 'super' ? '' : implode(',', $f['permissions']);
        if ($edite) {
            db()->prepare('UPDATE admins SET identifiant = ?, nom = ?, role = ?, permissions = ?, actif = ? WHERE id = ?')
                ->execute([$f['identifiant'], $f['nom'], $f['role'], $permissions, $f['actif'], $edite['id']]);
            if ($mdp !== '') {
                db()->prepare('UPDATE admins SET mot_de_passe = ? WHERE id = ?')->execute([password_hash($mdp, PASSWORD_DEFAULT), $edite['id']]);
            }
            flash('Compte « ' . $f['identifiant'] . ' » mis à jour.');
        } else {
            db()->prepare('INSERT INTO admins (identifiant, mot_de_passe, nom, role, permissions, actif) VALUES (?, ?, ?, ?, ?, 1)')
                ->execute([$f['identifiant'], password_hash($mdp, PASSWORD_DEFAULT), $f['nom'], $f['role'], $permissions]);
            flash('Compte « ' . $f['identifiant'] . ' » créé. Communiquez-lui son identifiant et son mot de passe.');
        }
        redirect('admin/utilisateurs.php');
    }
}

$comptes = db()->query('SELECT * FROM admins ORDER BY actif DESC, role DESC, identifiant')->fetchAll();

$titre = 'Utilisateurs';
$page = 'utilisateurs';
$actionEntete = $edite ? '<a href="' . url('admin/utilisateurs.php') . '" class="btn btn-rouge">+ Nouvel utilisateur</a>' : '';
require __DIR__ . '/_header.php';
?>

<div class="admin-2col">
    <section class="panneau">
        <h2>Comptes</h2>
        <div class="tableau-defilant">
            <table class="tableau tableau-admin">
                <thead><tr><th>Utilisateur</th><th>Accès</th><th>État</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($comptes as $c): ?>
                    <tr class="<?= $c['actif'] ? '' : 'inactif' ?>">
                        <td>
                            <strong><?= e($c['identifiant']) ?></strong>
                            <?php if ((int) $c['id'] === (int) $moi['id']): ?><small>(vous)</small><?php endif; ?>
                            <?php if ($c['nom']): ?><br><small><?= e($c['nom']) ?></small><?php endif; ?>
                        </td>
                        <td>
                            <?php if ($c['role'] === 'super'): ?>
                                <span class="etiquette etiquette-rouge">Administrateur principal</span>
                            <?php else: ?>
                                <?php foreach (permissions_admin($c) as $perm): ?>
                                    <span class="etiquette etiquette-bleu"><?= e(PERMISSIONS_ADMIN[$perm]) ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                        <td><?= $c['actif'] ? '<span class="etiquette etiquette-vert">Actif</span>' : '<span class="etiquette">Désactivé</span>' ?></td>
                        <td class="actions"><a href="<?= url('admin/utilisateurs.php?id=' . $c['id']) ?>" class="btn-mini">Modifier</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="panneau">
        <h2><?= $edite ? 'Modifier « ' . e($edite['identifiant']) . ' »' : 'Ajouter un utilisateur' ?></h2>
        <?php if ($erreurs): ?>
            <div class="alerte alerte-erreur"><?= implode('<br>', array_map('e', $erreurs)) ?></div>
        <?php endif; ?>
        <form method="post" class="formulaire" autocomplete="off">
            <?= csrf_field() ?>
            <label>Identifiant de connexion<input type="text" name="identifiant" value="<?= e($f['identifiant']) ?>" required maxlength="50" autocomplete="off"></label>
            <label>Nom affiché (facultatif)<input type="text" name="nom" value="<?= e($f['nom']) ?>" maxlength="100" placeholder="Ex. : Hery, magasin Ankasina"></label>
            <label><?= $edite ? 'Nouveau mot de passe (laisser vide pour ne pas le changer)' : 'Mot de passe (8 caractères minimum)' ?>
                <input type="password" name="mot_de_passe" <?= $edite ? '' : 'required' ?> minlength="8" autocomplete="new-password">
            </label>

            <fieldset class="groupe-cases">
                <legend>Rôle</legend>
                <?php foreach (ROLES_ADMIN as $val => $lib): ?>
                    <label class="case"><input type="radio" name="role" value="<?= $val ?>" <?= $f['role'] === $val ? 'checked' : '' ?>> <?= e($lib) ?></label>
                <?php endforeach; ?>
                <p class="petit">L’administrateur principal a tous les accès, gère les utilisateurs et les réglages du site.</p>
            </fieldset>

            <fieldset class="groupe-cases">
                <legend>Accès (pour un employé)</legend>
                <?php foreach (PERMISSIONS_ADMIN as $val => $lib): ?>
                    <label class="case"><input type="checkbox" name="permissions[]" value="<?= $val ?>" <?= in_array($val, $f['permissions'], true) ? 'checked' : '' ?>> <?= e($lib) ?></label>
                <?php endforeach; ?>
                <p class="petit">« Saisie des pièces » permet d’ajouter, modifier et masquer des pièces. Seul l’administrateur principal peut les supprimer définitivement.</p>
            </fieldset>

            <?php if ($edite): ?>
                <label class="case"><input type="checkbox" name="actif" <?= $f['actif'] ? 'checked' : '' ?>> Compte actif (décocher pour bloquer l’accès immédiatement)</label>
            <?php endif; ?>

            <div class="barre-actions">
                <?php if ($edite): ?><a href="<?= url('admin/utilisateurs.php') ?>" class="btn btn-contour">Annuler</a><?php endif; ?>
                <button class="btn btn-rouge"><?= $edite ? 'Enregistrer' : 'Créer le compte' ?></button>
            </div>
        </form>
    </section>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
