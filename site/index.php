<?php
require_once __DIR__ . '/includes/bootstrap.php';

$promos = db()->query('SELECT * FROM pieces WHERE actif = 1 AND ' . promo_active_sql() . ' ORDER BY updated_at DESC LIMIT 8')->fetchAll();
$arrivages = db()->query('SELECT * FROM pieces WHERE actif = 1 AND nouveau = 1 ORDER BY created_at DESC, id DESC LIMIT 8')->fetchAll();
$nbParCategorie = db()->query('SELECT categorie, COUNT(*) n FROM pieces WHERE actif = 1 GROUP BY categorie')->fetchAll(PDO::FETCH_KEY_PAIR);

$titre = SITE_NAME;
$page = 'accueil';
$canonical = url('');
$description = 'Corée Auto, votre référence en pièces détachées d’origine coréenne à Madagascar : Hyundai, Kia, SsangYong, Daewoo. Promotions, nouveaux arrivages, garage et atelier mécanique. Antananarivo et Tamatave.';
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="conteneur hero-grille">
        <div class="hero-texte">
            <p class="hero-sur-titre">Pièces d’origine coréenne</p>
            <h1>Votre référence en pièces <span>détachées coréennes</span></h1>
            <p class="hero-intro">Hyundai, Kia, SsangYong, Daewoo… Freinage, transmission, suspension, moteur : trouvez la bonne pièce et retirez-la dans l’un de nos 3 magasins.</p>
            <div class="hero-actions">
                <a href="<?= url('catalogue') ?>" class="btn btn-blanc">Voir le catalogue</a>
                <?php if (promotions_visibles()): ?>
                <a href="<?= url('promotions') ?>" class="btn btn-rouge">Promotions en cours</a>
                <?php else: ?>
                <a href="<?= url('magasins') ?>" class="btn btn-rouge">Nos magasins</a>
                <?php endif; ?>
            </div>
            <ul class="hero-services">
                <li>Vente pièces détachées</li>
                <li>Garage</li>
            </ul>
        </div>
        <div class="hero-visuel">
            <img src="<?= url('assets/img/arrivage.jpg') ?>" alt="Arrivage pièces dans tous nos magasins">
        </div>
    </div>
</section>

<section class="marques">
    <div class="conteneur marques-liste">
        <span class="marques-titre">Choisissez votre marque</span>
        <?php foreach (MARQUES as $m): ?>
            <a href="<?= lien_marque($m) ?>" class="marque-puce"><?= e($m) ?></a>
        <?php endforeach; ?>
    </div>
</section>

<?php if ($promos && promotions_visibles()): ?>
<section class="section">
    <div class="conteneur">
        <div class="section-entete">
            <h2><span class="trait-rouge"></span>Promotions du moment</h2>
            <a href="<?= url('promotions') ?>" class="lien-fleche">Toutes les promotions →</a>
        </div>
        <div class="grille-cartes">
            <?php foreach ($promos as $p) { require __DIR__ . '/includes/carte-piece.php'; } ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($arrivages): ?>
<section class="section section-grise">
    <div class="conteneur">
        <div class="section-entete">
            <h2><span class="trait-bleu"></span>Nouveaux arrivages</h2>
            <a href="<?= url('arrivages') ?>" class="lien-fleche">Voir tous les arrivages →</a>
        </div>
        <div class="grille-cartes">
            <?php foreach ($arrivages as $p) { require __DIR__ . '/includes/carte-piece.php'; } ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section">
    <div class="conteneur">
        <div class="section-entete">
            <h2><span class="trait-rouge"></span>Rechercher par catégorie</h2>
        </div>
        <div class="grille-categories">
            <?php foreach (CATEGORIES as $c): ?>
                <a href="<?= lien_categorie($c) ?>" class="categorie">
                    <span class="categorie-nom"><?= e($c) ?></span>
                    <span class="categorie-nb"><?= (int) ($nbParCategorie[$c] ?? 0) ?> pièce(s)</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section-grise">
    <div class="conteneur encart-atelier">
        <div>
            <p class="sur-titre-rouge">Notre atelier</p>
            <h2>Garage spécialisé véhicules coréens</h2>
            <p>Diagnostic électronique, entretien, freinage, embrayage et suspension. Nos mécaniciens montent les pièces que vous achetez chez nous.</p>
            <a href="<?= url('atelier') ?>" class="btn btn-bleu">Découvrir l’atelier</a>
            <a href="<?= url('atelier#rendez-vous') ?>" class="btn btn-rouge">Prendre rendez-vous</a>
        </div>
        <ul class="encart-atelier-liste">
            <?php foreach (SERVICES_ATELIER as $s): ?><li><?= e($s['titre']) ?></li><?php endforeach; ?>
        </ul>
    </div>
</section>

<section class="bandeau-contact">
    <div class="conteneur bandeau-grille">
        <div>
            <h2>Vous ne trouvez pas votre pièce ?</h2>
            <p>Envoyez-nous la référence ou une photo, nous la trouvons pour vous.</p>
        </div>
        <div class="bandeau-magasins">
            <?php foreach (MAGASINS as $m): ?>
                <a href="tel:<?= e(str_replace(' ', '', $m['tel'])) ?>">
                    <span><?= e($m['nom']) ?></span>
                    <strong><?= e($m['tel']) ?></strong>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
