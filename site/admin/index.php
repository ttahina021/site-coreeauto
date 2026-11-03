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
$commandes = peut('commandes') ? $pdo->query('SELECT * FROM commandes ORDER BY id DESC LIMIT 6')->fetchAll() : null;
$recentes = peut('pieces') ? $pdo->query('SELECT * FROM pieces ORDER BY id DESC LIMIT 6')->fetchAll() : null;

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
                <thead><tr><th>N°</th><th>Client</th><th>Date</th><th>Statut</th></tr></thead>
                <tbody>
                <?php foreach ($commandes as $c): ?>
                    <tr>
                        <td><a href="<?= url('admin/commandes.php?id=' . $c['id']) ?>">#<?= $c['id'] ?></a></td>
                        <td><?= e($c['nom']) ?><br><small><?= e($c['telephone']) ?></small></td>
                        <td><?= e(date('d/m H:i', strtotime($c['created_at']))) ?></td>
                        <td><span class="statut statut-<?= e($c['statut']) ?>"><?= e(STATUTS_COMMANDE[$c['statut']] ?? $c['statut']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ($recentes !== null): ?>
    <section class="panneau">
        <h2>Dernières pièces ajoutées <a href="<?= url('admin/pieces.php') ?>">Gérer les pièces</a></h2>
        <?php if (!$recentes): ?>
            <p class="petit">Aucune pièce pour l’instant.</p>
        <?php else: ?>
            <table class="tableau">
                <thead><tr><th>Pièce</th><th>Statut</th></tr></thead>
                <tbody>
                <?php foreach ($recentes as $p): ?>
                    <tr>
                        <td><a href="<?= url('admin/piece.php?id=' . $p['id']) ?>"><?= e($p['nom']) ?></a><br><small><?= e($p['marque']) ?> · <?= e($p['categorie']) ?></small></td>
                        <td><?= $p['actif'] ? '<span class="etiquette etiquette-vert">En ligne</span>' : '<span class="etiquette">Masquée</span>' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
