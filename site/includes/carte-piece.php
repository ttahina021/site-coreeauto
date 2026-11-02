<?php /** @var array $p */ $enPromo = promo_active($p); ?>
<article class="carte">
    <a href="<?= lien_piece($p) ?>" class="carte-image">
        <img src="<?= e(piece_image($p)) ?>" alt="<?= e($p['nom']) ?>" loading="lazy">
        <?php if ($enPromo): ?><span class="badge badge-promo">-<?= remise_pct($p) ?> %</span><?php endif; ?>
        <?php if ($p['nouveau']): ?><span class="badge badge-nouveau">Arrivage</span><?php endif; ?>
    </a>
    <div class="carte-corps">
        <div class="carte-meta"><?= e($p['marque']) ?> · <?= e($p['categorie']) ?></div>
        <h3 class="carte-titre"><a href="<?= lien_piece($p) ?>"><?= e($p['nom']) ?></a></h3>
        <?php if ($p['modeles']): ?><div class="carte-modeles"><?= e($p['modeles']) ?></div><?php endif; ?>
        <div class="carte-ref">Réf. <?= e($p['reference']) ?></div>
        <div class="carte-bas">
            <div class="prix-bloc">
                <?php if ($enPromo): ?>
                    <span class="prix-ancien"><?= prix((int) $p['prix']) ?></span>
                    <span class="prix prix-promo"><?= prix(prix_final($p)) ?></span>
                <?php else: ?>
                    <span class="prix"><?= prix((int) $p['prix']) ?></span>
                <?php endif; ?>
            </div>
            <?php if ((int) $p['stock'] > 0): ?>
                <form action="<?= url('panier') ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="ajouter">
                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                    <button class="btn-ajout" type="submit" aria-label="Ajouter au panier">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                    </button>
                </form>
            <?php else: ?>
                <span class="rupture">Sur commande</span>
            <?php endif; ?>
        </div>
    </div>
</article>
