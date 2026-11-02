<?php
require_once __DIR__ . '/includes/bootstrap.php';

$erreurs = [];
$form = ['nom' => '', 'telephone' => '', 'email' => '', 'magasin' => MAGASINS[0]['nom'], 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'ajouter' && ($p = get_piece($id))) {
        $qte = max(1, (int) ($_POST['quantite'] ?? 1));
        panier_set($id, (panier()[$id] ?? 0) + $qte);
        flash('« ' . $p['nom'] . ' » a été ajouté au panier.');
        $retour = $_SERVER['HTTP_REFERER'] ?? '';
        if ($retour && parse_url($retour, PHP_URL_HOST) === ($_SERVER['HTTP_HOST'] ?? '') && !str_contains($retour, '/panier')) {
            header('Location: ' . $retour);
            exit;
        }
        redirect('panier');
    }

    if ($action === 'maj' && isset($_POST['supprimer'])) {
        panier_set((int) $_POST['supprimer'], 0);
        redirect('panier');
    }

    if ($action === 'maj') {
        foreach ((array) ($_POST['quantites'] ?? []) as $pid => $qte) {
            panier_set((int) $pid, (int) $qte);
        }
        flash('Panier mis à jour.');
        redirect('panier');
    }

    if ($action === 'commander') {
        foreach ($form as $k => $_) {
            $form[$k] = trim((string) ($_POST[$k] ?? ''));
        }
        if ($form['nom'] === '') {
            $erreurs[] = 'Indiquez votre nom.';
        }
        if (!preg_match('/^[0-9 +().-]{8,20}$/', $form['telephone'])) {
            $erreurs[] = 'Indiquez un numéro de téléphone valide.';
        }
        if ($form['email'] !== '' && !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
            $erreurs[] = 'L’adresse e-mail n’est pas valide.';
        }
        if (!in_array($form['magasin'], array_column(MAGASINS, 'nom'), true)) {
            $erreurs[] = 'Choisissez un magasin de retrait.';
        }
        $detail = panier_detail();
        if (!$detail['lignes']) {
            $erreurs[] = 'Votre panier est vide.';
        }

        if (!$erreurs) {
            $pdo = db();
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('INSERT INTO commandes (nom, telephone, email, magasin, message, total) VALUES (?,?,?,?,?,?)');
            $stmt->execute([$form['nom'], $form['telephone'], $form['email'], $form['magasin'], $form['message'], $detail['total']]);
            $commandeId = (int) $pdo->lastInsertId();
            $ligne = $pdo->prepare('INSERT INTO commande_lignes (commande_id, piece_id, reference, nom, prix_unitaire, quantite) VALUES (?,?,?,?,?,?)');
            foreach ($detail['lignes'] as $l) {
                $ligne->execute([$commandeId, $l['piece']['id'], $l['piece']['reference'], $l['piece']['nom'], $l['prix_unitaire'], $l['quantite']]);
            }
            $pdo->commit();

            // E-mail à la boutique, et copie au client s'il a donné son adresse
            $lignesTxt = '';
            foreach ($detail['lignes'] as $l) {
                $lignesTxt .= '- ' . $l['quantite'] . ' x ' . $l['piece']['nom'] . ' (réf. ' . $l['piece']['reference'] . ') : ' . prix($l['sous_total']) . "\n";
            }
            $recap = "Commande n° $commandeId\n\n" . $lignesTxt . "\nTotal : " . prix($detail['total']) . "\n\n"
                . 'Nom : ' . $form['nom'] . "\nTéléphone : " . $form['telephone'] . "\nE-mail : " . ($form['email'] ?: '-')
                . "\nMagasin de retrait : " . $form['magasin'] . ($form['message'] !== '' ? "\n\nMessage :\n" . $form['message'] : '');
            envoyer_mail(mail_boutique(), 'Nouvelle commande n° ' . $commandeId . ' - ' . $form['nom'], $recap
                . "\n\nGérer la commande : " . url_absolue(url('admin/commandes.php?id=' . $commandeId)), $form['email'] ?: null);
            if ($form['email'] !== '') {
                envoyer_mail($form['email'], 'Votre commande n° ' . $commandeId . ' chez ' . SITE_NAME,
                    'Bonjour ' . $form['nom'] . ",\n\nMerci pour votre commande. Nous vous appelons rapidement pour confirmer la disponibilité et le retrait au magasin.\n\n"
                    . $recap . "\n\nPas de paiement en ligne : vous réglez au retrait.\n\n" . SITE_NAME . "\n" . url_absolue(url('')), mail_boutique());
            }

            $_SESSION['panier'] = [];
            $_SESSION['derniere_commande'] = $commandeId;
            redirect('panier?confirmation=' . $commandeId);
        }
    }
}

// Page de confirmation après commande
if (isset($_GET['confirmation']) && (int) $_GET['confirmation'] === ($_SESSION['derniere_commande'] ?? -1)) {
    $cid = (int) $_GET['confirmation'];
    $cmd = db()->query('SELECT * FROM commandes WHERE id = ' . $cid)->fetch();
    $lignes = db()->query('SELECT * FROM commande_lignes WHERE commande_id = ' . $cid)->fetchAll();
    $texte = "Bonjour Corée Auto, voici ma commande n° $cid :\n";
    foreach ($lignes as $l) {
        $texte .= "- {$l['quantite']} x {$l['nom']} (réf. {$l['reference']})\n";
    }
    $texte .= 'Total : ' . prix((int) $cmd['total']) . "\nRetrait : {$cmd['magasin']}";

    $titre = 'Commande envoyée';
    $robots = 'noindex';
    require __DIR__ . '/includes/header.php';
    ?>
    <section class="conteneur confirmation">
        <div class="confirmation-icone">✓</div>
        <h1>Merci <?= e($cmd['nom']) ?>, votre demande est bien reçue !</h1>
        <p>Commande n° <strong><?= $cid ?></strong> · Total <strong><?= prix((int) $cmd['total']) ?></strong></p>
        <p>Notre équipe vous rappelle au <strong><?= e($cmd['telephone']) ?></strong> pour confirmer la disponibilité et le retrait au magasin <strong><?= e($cmd['magasin']) ?></strong>.</p>
        <div class="hero-actions centre">
            <a class="btn btn-whatsapp" href="https://wa.me/<?= e(WHATSAPP) ?>?text=<?= rawurlencode($texte) ?>" target="_blank" rel="noopener">Envoyer aussi par WhatsApp</a>
            <a class="btn btn-bleu" href="<?= url('catalogue') ?>">Continuer mes achats</a>
        </div>
    </section>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$detail = panier_detail();
$titre = 'Mon panier';
$robots = 'noindex';
require __DIR__ . '/includes/header.php';
?>

<section class="page-titre">
    <div class="conteneur"><h1>Mon panier</h1></div>
</section>

<div class="conteneur panier">
    <?php if (!$detail['lignes']): ?>
        <div class="vide">
            <h2>Votre panier est vide</h2>
            <a href="<?= url('catalogue') ?>" class="btn btn-bleu">Parcourir le catalogue</a>
        </div>
    <?php else: ?>
        <div class="panier-grille">
            <form method="post" class="panier-lignes">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="maj">
                <button type="submit" class="visuellement-cache" tabindex="-1" aria-hidden="true">Mettre à jour</button>
                <table class="tableau">
                    <thead><tr><th>Pièce</th><th>Prix unitaire</th><th>Quantité</th><th>Sous-total</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($detail['lignes'] as $l): $p = $l['piece']; ?>
                        <tr>
                            <td class="panier-piece">
                                <img src="<?= e(piece_image($p)) ?>" alt="">
                                <div>
                                    <a href="<?= lien_piece($p) ?>"><strong><?= e($p['nom']) ?></strong></a>
                                    <small>Réf. <?= e($p['reference']) ?></small>
                                </div>
                            </td>
                            <td data-label="Prix">
                                <?php if (promo_active($p)): ?><span class="prix-ancien"><?= prix((int) $p['prix']) ?></span><br><?php endif; ?>
                                <?= prix($l['prix_unitaire']) ?>
                            </td>
                            <td data-label="Quantité"><input type="number" name="quantites[<?= (int) $p['id'] ?>]" value="<?= (int) $l['quantite'] ?>" min="0" max="99" class="champ-qte"></td>
                            <td data-label="Sous-total"><strong><?= prix($l['sous_total']) ?></strong></td>
                            <td>
                                <button type="submit" name="supprimer" value="<?= (int) $p['id'] ?>" class="btn-supprimer" aria-label="Retirer cette pièce">✕</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit" class="btn btn-contour">Mettre à jour le panier</button>
            </form>

            <aside class="panier-resume">
                <h2>Récapitulatif</h2>
                <div class="total-ligne"><span>Total</span><strong><?= prix($detail['total']) ?></strong></div>
                <p class="petit">Paiement au retrait en magasin. Nous vous appelons pour confirmer la disponibilité.</p>

                <?php if ($erreurs): ?>
                    <div class="alerte alerte-erreur"><?= implode('<br>', array_map('e', $erreurs)) ?></div>
                <?php endif; ?>

                <form method="post" class="formulaire">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="commander">
                    <label>Nom complet *<input type="text" name="nom" value="<?= e($form['nom']) ?>" required></label>
                    <label>Téléphone *<input type="tel" name="telephone" value="<?= e($form['telephone']) ?>" placeholder="034 00 000 00" required></label>
                    <label>E-mail<input type="email" name="email" value="<?= e($form['email']) ?>"></label>
                    <label>Magasin de retrait *
                        <select name="magasin">
                            <?php foreach (MAGASINS as $m): ?>
                                <option <?= $form['magasin'] === $m['nom'] ? 'selected' : '' ?>><?= e($m['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Message (modèle, année du véhicule…)<textarea name="message" rows="3"><?= e($form['message']) ?></textarea></label>
                    <button type="submit" class="btn btn-rouge btn-bloc">Envoyer ma commande</button>
                </form>
            </aside>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
