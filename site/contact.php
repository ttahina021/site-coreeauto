<?php
require_once __DIR__ . '/includes/bootstrap.php';

$titre = 'Nos magasins à Antananarivo et Tamatave';
$page = 'contact';
$canonical = url('magasins');
$description = 'Magasins Corée Auto : Ankasina 67ha (033 51 777 78), Anosizato Est (033 60 777 77) et Tamatave Bazar be (033 72 606 72). Ouvert du lundi au samedi.';
require __DIR__ . '/includes/header.php';
?>

<section class="page-titre">
    <div class="conteneur">
        <h1>Nos magasins</h1>
        <p>Retrait des commandes, conseil et garage dans nos 3 points de vente.</p>
    </div>
</section>

<section class="conteneur section">
    <div class="grille-magasins">
        <?php foreach (MAGASINS as $m): ?>
            <div class="magasin">
                <iframe class="magasin-carte" src="https://www.google.com/maps?q=<?= rawurlencode($m['carte']) ?>&amp;z=<?= preg_match('/^-?[\d.]+,-?[\d.]+$/', $m['carte']) ? 17 : 15 ?>&amp;hl=fr&amp;output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Carte <?= e($m['nom']) ?>"></iframe>
                <h2><?= e($m['nom']) ?></h2>
                <p><?= e($m['adresse']) ?></p>
                <a class="magasin-tel" href="tel:<?= e(str_replace(' ', '', $m['tel'])) ?>"><?= e($m['tel']) ?></a>
                <a class="lien-fleche" href="https://www.google.com/maps/search/?api=1&amp;query=<?= rawurlencode($m['carte']) ?>" target="_blank" rel="noopener">Voir sur la carte →</a>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="contact-infos">
        <div>
            <h2>Horaires d’ouverture</h2>
            <table class="horaires">
                <?php foreach (HORAIRES as $jour => $h): ?>
                    <tr><td><?= e($jour) ?></td><td><strong><?= e($h) ?></strong></td></tr>
                <?php endforeach; ?>
            </table>
        </div>
        <div>
            <h2>Nos services</h2>
            <ul class="liste-services">
                <li><strong>Vente pièces détachées</strong> d’origine coréenne</li>
                <li><strong>Garage</strong> : entretien et réparation de véhicules coréens</li>
            </ul>
            <div class="hero-actions">
                <a class="btn btn-whatsapp" href="https://wa.me/<?= e(WHATSAPP) ?>" target="_blank" rel="noopener">WhatsApp</a>
                <a class="btn btn-bleu" href="mailto:<?= e(SITE_EMAIL) ?>"><?= e(SITE_EMAIL) ?></a>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
