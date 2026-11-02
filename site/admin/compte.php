<?php
require __DIR__ . '/../includes/bootstrap.php';
exiger_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $stmt = db()->prepare('SELECT * FROM admins WHERE id = ?');
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();
    $nouveau = $_POST['nouveau'] ?? '';
    if (!$admin || !password_verify($_POST['actuel'] ?? '', $admin['mot_de_passe'])) {
        flash('Mot de passe actuel incorrect.', 'erreur');
    } elseif (strlen($nouveau) < 8) {
        flash('Le nouveau mot de passe doit faire au moins 8 caractères.', 'erreur');
    } elseif ($nouveau !== ($_POST['confirmation'] ?? '')) {
        flash('Les deux mots de passe ne correspondent pas.', 'erreur');
    } else {
        db()->prepare('UPDATE admins SET mot_de_passe = ? WHERE id = ?')->execute([password_hash($nouveau, PASSWORD_DEFAULT), $admin['id']]);
        flash('Mot de passe modifié.');
    }
    redirect('admin/compte.php');
}

$titre = 'Mon compte';
$page = 'compte';
require __DIR__ . '/_header.php';
?>

<section class="panneau panneau-etroit">
    <h2>Changer le mot de passe</h2>
    <p class="petit">Connecté en tant que <strong><?= e($_SESSION['admin_nom'] ?? '') ?></strong>.</p>
    <form method="post" class="formulaire">
        <?= csrf_field() ?>
        <label>Mot de passe actuel<input type="password" name="actuel" required autocomplete="current-password"></label>
        <label>Nouveau mot de passe (8 caractères minimum)<input type="password" name="nouveau" required minlength="8" autocomplete="new-password"></label>
        <label>Confirmer le nouveau mot de passe<input type="password" name="confirmation" required minlength="8" autocomplete="new-password"></label>
        <button class="btn btn-rouge">Enregistrer</button>
    </form>
</section>

<?php require __DIR__ . '/_footer.php'; ?>
