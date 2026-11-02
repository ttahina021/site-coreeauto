<?php
require_once __DIR__ . '/includes/bootstrap.php';

$services = array_column(SERVICES_ATELIER, 'titre');
$erreurs = [];
$f = ['nom' => '', 'telephone' => '', 'vehicule' => '', 'service' => $services[0], 'magasin' => MAGASINS[0]['nom'], 'date_souhaitee' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($f as $k => $_) {
        $f[$k] = trim((string) ($_POST[$k] ?? ''));
    }
    if ($f['nom'] === '') $erreurs[] = 'Indiquez votre nom.';
    if (!preg_match('/^[0-9 +().-]{8,20}$/', $f['telephone'])) $erreurs[] = 'Indiquez un numéro de téléphone valide.';
    if (!in_array($f['service'], $services, true)) $f['service'] = 'Autre';
    if (!in_array($f['magasin'], array_column(MAGASINS, 'nom'), true)) $erreurs[] = 'Choisissez un magasin.';
    if ($f['date_souhaitee'] !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['date_souhaitee']) || $f['date_souhaitee'] < date('Y-m-d'))) {
        $erreurs[] = 'Choisissez une date à partir d’aujourd’hui.';
    }

    if (!$erreurs) {
        db()->prepare('INSERT INTO rendez_vous (nom, telephone, vehicule, service, magasin, date_souhaitee, message) VALUES (?,?,?,?,?,?,?)')
            ->execute([$f['nom'], $f['telephone'], $f['vehicule'], $f['service'], $f['magasin'], $f['date_souhaitee'] ?: null, $f['message']]);
        redirect('atelier?envoye=1#rendez-vous');
    }
}

$titre = 'Atelier, garage et Clinic injector pour véhicules coréens';
$page = 'atelier';
$canonical = url('atelier');
$description = 'Atelier Corée Auto : diagnostic électronique, Clinic injector (test d’injecteurs diesel), entretien, freinage, embrayage et suspension pour Hyundai, Kia, SsangYong. Prenez rendez-vous en ligne.';
if (!empty($_GET['envoye'])) {
    $robots = 'noindex';
}
require __DIR__ . '/includes/header.php';
?>

<section class="page-titre page-titre-atelier">
    <div class="conteneur">
        <p class="hero-sur-titre">Garage · Clinic injector</p>
        <h1>L’atelier Corée Auto</h1>
        <p>Des mécaniciens spécialisés Hyundai, Kia, SsangYong et Daewoo, avec les bonnes pièces sur place.</p>
        <div class="hero-actions">
            <a href="#rendez-vous" class="btn btn-blanc">Prendre rendez-vous</a>
            <a href="https://wa.me/<?= e(WHATSAPP) ?>?text=<?= rawurlencode('Bonjour, je souhaite un rendez-vous à l’atelier Corée Auto.') ?>" class="btn btn-whatsapp" target="_blank" rel="noopener">WhatsApp</a>
        </div>
    </div>
</section>

<section class="section">
    <div class="conteneur">
        <div class="section-entete"><h2><span class="trait-rouge"></span>Nos prestations</h2></div>
        <div class="grille-services">
            <?php foreach (SERVICES_ATELIER as $i => $s): ?>
                <article class="service">
                    <span class="service-num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
                    <h3><?= e($s['titre']) ?></h3>
                    <p><?= e($s['texte']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section-grise">
    <div class="conteneur">
        <div class="section-entete"><h2><span class="trait-bleu"></span>Comment ça se passe</h2></div>
        <ol class="etapes">
            <li><strong>Rendez-vous</strong><span>Réservez en ligne, par téléphone ou sur WhatsApp.</span></li>
            <li><strong>Diagnostic</strong><span>Nous contrôlons le véhicule et vous donnons un devis avant toute intervention.</span></li>
            <li><strong>Réparation</strong><span>Montage de pièces d’origine coréenne, disponibles directement en magasin.</span></li>
            <li><strong>Restitution</strong><span>Essai du véhicule et explication des travaux réalisés.</span></li>
        </ol>
    </div>
</section>

<section class="section" id="rendez-vous">
    <div class="conteneur rdv-grille">
        <div>
            <div class="section-entete"><h2><span class="trait-rouge"></span>Prendre rendez-vous</h2></div>
            <p>Remplissez le formulaire : nous vous rappelons pour confirmer le jour et l’heure.</p>
            <h3 class="rdv-sous-titre">Horaires de l’atelier</h3>
            <table class="horaires">
                <?php foreach (HORAIRES as $jour => $h): ?>
                    <tr><td><?= e($jour) ?></td><td><strong><?= e($h) ?></strong></td></tr>
                <?php endforeach; ?>
            </table>
            <h3 class="rdv-sous-titre">Par téléphone</h3>
            <ul class="liste-services">
                <?php foreach (MAGASINS as $m): ?>
                    <li><?= e($m['nom']) ?> : <a href="tel:<?= e(str_replace(' ', '', $m['tel'])) ?>"><strong><?= e($m['tel']) ?></strong></a></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <form method="post" action="<?= url('atelier#rendez-vous') ?>" class="formulaire rdv-formulaire">
            <?php if (!empty($_GET['envoye'])): ?>
                <div class="alerte alerte-succes">Merci ! Votre demande de rendez-vous est envoyée. Nous vous rappelons pour confirmer le jour et l’heure.</div>
            <?php endif; ?>
            <?php if ($erreurs): ?><div class="alerte alerte-erreur"><?= implode('<br>', array_map('e', $erreurs)) ?></div><?php endif; ?>
            <?= csrf_field() ?>
            <div class="champs-2">
                <label>Nom complet *<input type="text" name="nom" value="<?= e($f['nom']) ?>" required></label>
                <label>Téléphone *<input type="tel" name="telephone" value="<?= e($f['telephone']) ?>" placeholder="034 00 000 00" required></label>
            </div>
            <label>Véhicule (marque, modèle, année)<input type="text" name="vehicule" value="<?= e($f['vehicule']) ?>" placeholder="Ex. Hyundai H1 2012 diesel"></label>
            <div class="champs-2">
                <label>Prestation
                    <select name="service">
                        <?php foreach ($services as $s): ?><option <?= $f['service'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
                        <option <?= $f['service'] === 'Autre' ? 'selected' : '' ?>>Autre</option>
                    </select>
                </label>
                <label>Atelier
                    <select name="magasin">
                        <?php foreach (MAGASINS as $m): ?><option <?= $f['magasin'] === $m['nom'] ? 'selected' : '' ?>><?= e($m['nom']) ?></option><?php endforeach; ?>
                    </select>
                </label>
            </div>
            <label>Date souhaitée<input type="date" name="date_souhaitee" value="<?= e($f['date_souhaitee']) ?>" min="<?= date('Y-m-d') ?>"></label>
            <label>Décrivez le problème<textarea name="message" rows="4" placeholder="Bruit, voyant allumé, fumée…"><?= e($f['message']) ?></textarea></label>
            <button type="submit" class="btn btn-rouge btn-bloc">Envoyer ma demande</button>
        </form>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
