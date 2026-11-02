<?php
require __DIR__ . '/../includes/bootstrap.php';
exiger_admin('commandes');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) ($_POST['id'] ?? 0);
    $statut = $_POST['statut'] ?? '';
    if (isset(STATUTS_COMMANDE[$statut])) {
        db()->prepare('UPDATE commandes SET statut = ? WHERE id = ?')->execute([$statut, $id]);
        flash('Commande #' . $id . ' : statut « ' . STATUTS_COMMANDE[$statut] . ' ».');
    }
    redirect('admin/commandes.php?id=' . $id);
}

$id = (int) ($_GET['id'] ?? 0);
$commande = null;
if ($id) {
    $stmt = db()->prepare('SELECT * FROM commandes WHERE id = ?');
    $stmt->execute([$id]);
    $commande = $stmt->fetch();
    $lignes = db()->prepare('SELECT * FROM commande_lignes WHERE commande_id = ?');
    $lignes->execute([$id]);
    $lignes = $lignes->fetchAll();
}

$filtre = $_GET['statut'] ?? '';
if (isset(STATUTS_COMMANDE[$filtre])) {
    $stmt = db()->prepare('SELECT * FROM commandes WHERE statut = ? ORDER BY id DESC');
    $stmt->execute([$filtre]);
} else {
    $stmt = db()->query('SELECT * FROM commandes ORDER BY id DESC');
}
$commandes = $stmt->fetchAll();

$titre = $commande ? 'Commande #' . $commande['id'] : 'Commandes';
$page = 'commandes';
require __DIR__ . '/_header.php';
?>

<?php if ($commande): ?>
    <div class="admin-2col">
        <section class="panneau">
            <h2>Détail</h2>
            <table class="tableau">
                <thead><tr><th>Pièce</th><th>Qté</th><th>Prix unitaire</th><th>Sous-total</th></tr></thead>
                <tbody>
                <?php foreach ($lignes as $l): ?>
                    <tr>
                        <td><?= e($l['nom']) ?><br><small><?= e($l['reference']) ?></small></td>
                        <td><?= (int) $l['quantite'] ?></td>
                        <td><?= prix((int) $l['prix_unitaire']) ?></td>
                        <td><strong><?= prix($l['prix_unitaire'] * $l['quantite']) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="total-ligne"><span>Total</span><strong><?= prix((int) $commande['total']) ?></strong></div>
        </section>
        <section class="panneau">
            <h2>Client</h2>
            <p><strong><?= e($commande['nom']) ?></strong><br>
                <a href="tel:<?= e($commande['telephone']) ?>"><?= e($commande['telephone']) ?></a><br>
                <?= $commande['email'] ? '<a href="mailto:' . e($commande['email']) . '">' . e($commande['email']) . '</a><br>' : '' ?>
                Retrait : <strong><?= e($commande['magasin']) ?></strong><br>
                Reçue le <?= e(date('d/m/Y à H:i', strtotime($commande['created_at']))) ?></p>
            <?php if ($commande['message']): ?><p class="message-client"><?= nl2br(e($commande['message'])) ?></p><?php endif; ?>
            <form method="post" class="formulaire">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $commande['id'] ?>">
                <label>Statut
                    <select name="statut">
                        <?php foreach (STATUTS_COMMANDE as $k => $v): ?>
                            <option value="<?= $k ?>" <?= $commande['statut'] === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button class="btn btn-bleu">Mettre à jour</button>
            </form>
        </section>
    </div>
    <p><a href="<?= url('admin/commandes.php') ?>">← Toutes les commandes</a></p>
<?php else: ?>
    <div class="onglets">
        <a href="<?= url('admin/commandes.php') ?>" class="<?= $filtre === '' ? 'actif' : '' ?>">Toutes</a>
        <?php foreach (STATUTS_COMMANDE as $k => $v): ?>
            <a href="<?= url('admin/commandes.php?statut=' . $k) ?>" class="<?= $filtre === $k ? 'actif' : '' ?>"><?= e($v) ?></a>
        <?php endforeach; ?>
    </div>
    <section class="panneau">
        <?php if (!$commandes): ?>
            <p class="petit">Aucune commande.</p>
        <?php else: ?>
            <div class="tableau-defilant">
            <table class="tableau tableau-admin">
                <thead><tr><th>N°</th><th>Date</th><th>Client</th><th>Magasin</th><th>Total</th><th>Statut</th></tr></thead>
                <tbody>
                <?php foreach ($commandes as $c): ?>
                    <tr>
                        <td><a href="<?= url('admin/commandes.php?id=' . $c['id']) ?>"><strong>#<?= $c['id'] ?></strong></a></td>
                        <td><?= e(date('d/m/Y H:i', strtotime($c['created_at']))) ?></td>
                        <td><?= e($c['nom']) ?><br><small><?= e($c['telephone']) ?></small></td>
                        <td><?= e($c['magasin']) ?></td>
                        <td><?= prix((int) $c['total']) ?></td>
                        <td><span class="statut statut-<?= e($c['statut']) ?>"><?= e(STATUTS_COMMANDE[$c['statut']] ?? $c['statut']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/_footer.php'; ?>
