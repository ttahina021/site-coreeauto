<?php
require __DIR__ . '/../includes/bootstrap.php';
exiger_admin();

$pdo = db();
$stats = [];
if (peut('pieces')) {
    $stats['Pièces en ligne'] = (int) $pdo->query('SELECT COUNT(*) FROM pieces WHERE actif = 1')->fetchColumn();
}
if (peut('promotions')) {
    $stats['Promotions actives'] = (int) $pdo->query('SELECT COUNT(*) FROM pieces WHERE actif = 1 AND ' . promo_active_sql())->fetchColumn();
}
if (peut('commandes')) {
    $stats['Nouvelles commandes'] = (int) $pdo->query("SELECT COUNT(*) FROM commandes WHERE statut = 'nouvelle'")->fetchColumn();
}
if (peut('atelier')) {
    $stats['Rendez-vous à traiter'] = (int) $pdo->query("SELECT COUNT(*) FROM rendez_vous WHERE statut = 'nouveau'")->fetchColumn();
}
if (peut('pieces')) {
    $stats['Pièces en rupture'] = (int) $pdo->query('SELECT COUNT(*) FROM pieces WHERE actif = 1 AND stock <= 0')->fetchColumn();
}
$commandes = peut('commandes') ? $pdo->query('SELECT * FROM commandes ORDER BY id DESC LIMIT 6')->fetchAll() : null;
$stockBas = peut('pieces') ? $pdo->query('SELECT * FROM pieces WHERE actif = 1 AND stock <= 3 ORDER BY stock ASC LIMIT 6')->fetchAll() : null;

$titre = 'Tableau de bord';
$page = 'tableau';
$actionEntete = peut('pieces') ? '<a href="' . url('admin/piece.php') . '" class="btn btn-rouge">+ Ajouter une pièce</a>' : '';
require __DIR__ . '/_header.php';
?>

<div class="stats">
    <?php foreach ($stats as $label => $valeur): ?>
        <div class="stat"><span><?= e($label) ?></span><strong><?= $valeur ?></strong></div>
    <?php endforeach; ?>
</div>

<?php if (!$stats): ?>
    <section class="panneau"><p>Votre compte n’a encore aucun accès. Demandez à l’administrateur de vous en attribuer.</p></section>
<?php endif; ?>

<div class="admin-2col">
    <?php if ($commandes !== null): ?>
    <section class="panneau">
        <h2>Dernières commandes <a href="<?= url('admin/commandes.php') ?>">Tout voir</a></h2>
        <?php if (!$commandes): ?>
            <p class="petit">Aucune commande pour l’instant.</p>
        <?php else: ?>
            <table class="tableau">
                <thead><tr><th>N°</th><th>Client</th><th>Total</th><th>Statut</th></tr></thead>
                <tbody>
                <?php foreach ($commandes as $c): ?>
                    <tr>
                        <td><a href="<?= url('admin/commandes.php?id=' . $c['id']) ?>">#<?= $c['id'] ?></a></td>
                        <td><?= e($c['nom']) ?><br><small><?= e($c['telephone']) ?></small></td>
                        <td><?= prix((int) $c['total']) ?></td>
                        <td><span class="statut statut-<?= e($c['statut']) ?>"><?= e(STATUTS_COMMANDE[$c['statut']] ?? $c['statut']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ($stockBas !== null): ?>
    <section class="panneau">
        <h2>Stock faible <a href="<?= url('admin/pieces.php') ?>">Gérer les pièces</a></h2>
        <?php if (!$stockBas): ?>
            <p class="petit">Aucune pièce en stock faible.</p>
        <?php else: ?>
            <table class="tableau">
                <thead><tr><th>Pièce</th><th>Stock</th></tr></thead>
                <tbody>
                <?php foreach ($stockBas as $p): ?>
                    <tr>
                        <td><a href="<?= url('admin/piece.php?id=' . $p['id']) ?>"><?= e($p['nom']) ?></a><br><small><?= e($p['reference']) ?></small></td>
                        <td><strong class="<?= $p['stock'] <= 0 ? 'texte-rouge' : '' ?>"><?= (int) $p['stock'] ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
