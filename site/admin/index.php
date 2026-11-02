<?php
require __DIR__ . '/../includes/bootstrap.php';
exiger_admin();

$pdo = db();
$stats = [
    'Pièces en ligne' => (int) $pdo->query('SELECT COUNT(*) FROM pieces WHERE actif = 1')->fetchColumn(),
    'Promotions actives' => (int) $pdo->query('SELECT COUNT(*) FROM pieces WHERE actif = 1 AND ' . promo_active_sql())->fetchColumn(),
    'Nouvelles commandes' => (int) $pdo->query("SELECT COUNT(*) FROM commandes WHERE statut = 'nouvelle'")->fetchColumn(),
    'Pièces en rupture' => (int) $pdo->query('SELECT COUNT(*) FROM pieces WHERE actif = 1 AND stock <= 0')->fetchColumn(),
];
$commandes = $pdo->query('SELECT * FROM commandes ORDER BY id DESC LIMIT 6')->fetchAll();
$stockBas = $pdo->query('SELECT * FROM pieces WHERE actif = 1 AND stock <= 3 ORDER BY stock ASC LIMIT 6')->fetchAll();

$titre = 'Tableau de bord';
$page = 'tableau';
$actionEntete = '<a href="' . url('admin/piece.php') . '" class="btn btn-rouge">+ Ajouter une pièce</a>';
require __DIR__ . '/_header.php';
?>

<div class="stats">
    <?php foreach ($stats as $label => $valeur): ?>
        <div class="stat"><span><?= e($label) ?></span><strong><?= $valeur ?></strong></div>
    <?php endforeach; ?>
</div>

<div class="admin-2col">
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
</div>

<?php require __DIR__ . '/_footer.php'; ?>
