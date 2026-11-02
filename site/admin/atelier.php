<?php
require __DIR__ . '/../includes/bootstrap.php';
exiger_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) ($_POST['id'] ?? 0);
    $statut = $_POST['statut'] ?? '';
    if (isset(STATUTS_RDV[$statut])) {
        db()->prepare('UPDATE rendez_vous SET statut = ? WHERE id = ?')->execute([$statut, $id]);
        flash('Rendez-vous #' . $id . ' : « ' . STATUTS_RDV[$statut] . ' ».');
    }
    redirect('admin/atelier.php' . (isset($_GET['statut']) ? '?statut=' . urlencode($_GET['statut']) : ''));
}

$filtre = $_GET['statut'] ?? '';
if (isset(STATUTS_RDV[$filtre])) {
    $stmt = db()->prepare('SELECT * FROM rendez_vous WHERE statut = ? ORDER BY id DESC');
    $stmt->execute([$filtre]);
} else {
    $stmt = db()->query('SELECT * FROM rendez_vous ORDER BY id DESC');
}
$rdvs = $stmt->fetchAll();

$titre = 'Rendez-vous atelier';
$page = 'atelier';
$actionEntete = '<a href="' . url('atelier') . '" class="btn btn-contour" target="_blank">Voir la page Atelier ↗</a>';
require __DIR__ . '/_header.php';
?>

<div class="onglets">
    <a href="<?= url('admin/atelier.php') ?>" class="<?= $filtre === '' ? 'actif' : '' ?>">Tous</a>
    <?php foreach (STATUTS_RDV as $k => $v): ?>
        <a href="<?= url('admin/atelier.php?statut=' . $k) ?>" class="<?= $filtre === $k ? 'actif' : '' ?>"><?= e($v) ?></a>
    <?php endforeach; ?>
</div>

<section class="panneau">
    <?php if (!$rdvs): ?>
        <p class="petit">Aucune demande de rendez-vous.</p>
    <?php else: ?>
        <div class="tableau-defilant">
        <table class="tableau tableau-admin">
            <thead><tr><th>N°</th><th>Client</th><th>Véhicule / prestation</th><th>Atelier</th><th>Date souhaitée</th><th>Message</th><th>Statut</th></tr></thead>
            <tbody>
            <?php foreach ($rdvs as $r): ?>
                <tr>
                    <td><strong>#<?= $r['id'] ?></strong><br><small><?= e(date('d/m H:i', strtotime($r['created_at']))) ?></small></td>
                    <td><?= e($r['nom']) ?><br><a href="tel:<?= e($r['telephone']) ?>"><small><?= e($r['telephone']) ?></small></a></td>
                    <td><?= e($r['vehicule'] ?: '–') ?><br><small><?= e($r['service']) ?></small></td>
                    <td><?= e($r['magasin']) ?></td>
                    <td><?= $r['date_souhaitee'] ? e(date('d/m/Y', strtotime($r['date_souhaitee']))) : 'Indifférent' ?></td>
                    <td><small><?= nl2br(e($r['message'])) ?></small></td>
                    <td>
                        <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <select name="statut" onchange="this.form.submit()" class="select-statut statut-<?= e($r['statut']) ?>">
                                <?php foreach (STATUTS_RDV as $k => $v): ?>
                                    <option value="<?= $k ?>" <?= $r['statut'] === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/_footer.php'; ?>
