<?php
/** @var string $titre */
/** @var string $page */
$nbNouvelles = peut('commandes') ? (int) db()->query("SELECT COUNT(*) FROM commandes WHERE statut = 'nouvelle'")->fetchColumn() : 0;
$nbRdv = peut('atelier') ? (int) db()->query("SELECT COUNT(*) FROM rendez_vous WHERE statut = 'nouveau'")->fetchColumn() : 0;
$liens = ['tableau' => ['index.php', 'Tableau de bord']];
foreach (['pieces' => ['pieces.php', 'Pièces'], 'promotions' => ['promotions.php', 'Promotions'],
          'commandes' => ['commandes.php', 'Commandes'], 'atelier' => ['atelier.php', 'Rendez-vous atelier']] as $cle => $lien) {
    if (peut($cle)) {
        $liens[$cle] = $lien;
    }
}
if (est_super_admin()) {
    $liens['utilisateurs'] = ['utilisateurs.php', 'Utilisateurs'];
}
$liens['compte'] = ['compte.php', 'Mon compte'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= e($titre) ?> | Admin Corée Auto</title>
    <link rel="icon" href="<?= url('assets/img/favicon.png') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
</head>
<body class="admin">
<aside class="admin-menu">
    <a href="<?= url('admin/index.php') ?>" class="admin-logo"><span class="admin-logo-ca"><img src="<?= url('assets/img/logo-ca-admin.png') ?>" alt="Corée Auto" width="320" height="211"></span></a>
    <nav>
        <?php foreach ($liens as $cle => [$href, $label]): ?>
            <a href="<?= url('admin/' . $href) ?>" class="<?= $page === $cle ? 'actif' : '' ?>">
                <?= e($label) ?>
                <?php if ($cle === 'commandes' && $nbNouvelles): ?><span class="admin-pastille"><?= $nbNouvelles ?></span><?php endif; ?>
                <?php if ($cle === 'atelier' && $nbRdv): ?><span class="admin-pastille"><?= $nbRdv ?></span><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="admin-menu-bas">
        <span class="admin-qui">Connecté : <strong><?= e(admin_courant()['nom'] ?: admin_courant()['identifiant']) ?></strong></span>
        <a href="<?= url('') ?>" target="_blank">Voir le site ↗</a>
        <a href="<?= url('admin/logout.php') ?>">Se déconnecter</a>
    </div>
</aside>
<div class="admin-contenu">
    <header class="admin-entete">
        <h1><?= e($titre) ?></h1>
        <?php if (!empty($actionEntete)) echo $actionEntete; ?>
    </header>
    <?= flashes() ?>
