</main>

<footer class="pied">
    <div class="conteneur pied-grille">
        <div>
            <img src="<?= url('assets/img/logo-blanc.png') ?>" alt="Corée Auto" class="pied-logo">
            <p>Votre référence en pièces détachées coréennes à Madagascar.</p>
            <p class="pied-services">Vente pièces détachées · Garage · Clinic injector</p>
        </div>
        <div>
            <h3>Nos magasins</h3>
            <ul>
                <?php foreach (MAGASINS as $m): ?>
                    <li><strong><?= e($m['nom']) ?></strong><br><a href="tel:<?= e(str_replace(' ', '', $m['tel'])) ?>"><?= e($m['tel']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div>
            <h3>Horaires d’ouverture</h3>
            <ul>
                <?php foreach (HORAIRES as $jour => $h): ?>
                    <li><?= e($jour) ?> : <strong><?= e($h) ?></strong></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div>
            <h3>Contact</h3>
            <ul>
                <li><a href="mailto:<?= e(SITE_EMAIL) ?>"><?= e(SITE_EMAIL) ?></a></li>
                <li><a href="https://wa.me/<?= e(WHATSAPP) ?>" target="_blank" rel="noopener">WhatsApp</a></li>
                <li><?= e(SITE_URL) ?></li>
            </ul>
        </div>
    </div>
    <div class="pied-bas">
        <div class="conteneur pied-bas-contenu">
            <span>© <?= date('Y') ?> <?= e(SITE_NAME) ?>. Tous droits réservés.</span>
            <span class="credit">Developed with
                <svg class="coeur" viewBox="0 0 24 24" width="15" height="15" aria-label="amour" role="img"><path fill="#e40613" stroke="#fff" stroke-width="1.6" d="M12 21s-7.5-4.6-10-9.3C.3 8.4 2 4.5 5.8 4.1 8.1 3.9 9.7 5 12 7.4c2.3-2.4 3.9-3.5 6.2-3.3 3.8.4 5.5 4.3 3.8 7.6C19.5 16.4 12 21 12 21z"/></svg>
                by <a href="https://aricdigital.com" target="_blank" rel="noopener"><strong>Aric</strong></a></span>
        </div>
    </div>
</footer>

<a class="whatsapp-flottant" href="https://wa.me/<?= e(WHATSAPP) ?>" target="_blank" rel="noopener" aria-label="Nous écrire sur WhatsApp">
    <svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.7.8-.8 1-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.4.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6a2.7 2.7 0 0 0 1.8-1.2 2.2 2.2 0 0 0 .1-1.3c0-.1-.2-.2-.5-.3z"/></svg>
</a>

<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>
