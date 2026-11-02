<?php
// Fonctions utilitaires partagées par le site et l'espace admin.

/** Condition SQL « la promotion est en cours aujourd'hui » (compatible MySQL et SQLite). */
function promo_active_sql(): string
{
    $auj = "'" . date('Y-m-d') . "'";
    return "(promo_type IS NOT NULL AND promo_valeur > 0
        AND (promo_debut IS NULL OR promo_debut <= $auj)
        AND (promo_fin IS NULL OR promo_fin >= $auj))";
}

/** Date et heure actuelles, à insérer telles quelles dans une requête. */
function sql_maintenant(): string
{
    return "'" . date('Y-m-d H:i:s') . "'";
}

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function prix(int|float $montant): string
{
    return number_format((float) $montant, 0, ',', ' ') . ' ' . DEVISE;
}

// ---------- Promotions ----------

function promo_active(array $p): bool
{
    if (empty($p['promo_type']) || (int) $p['promo_valeur'] <= 0) {
        return false;
    }
    $today = date('Y-m-d');
    if (!empty($p['promo_debut']) && $p['promo_debut'] > $today) {
        return false;
    }
    if (!empty($p['promo_fin']) && $p['promo_fin'] < $today) {
        return false;
    }
    return prix_final($p, true) < (int) $p['prix'];
}

function prix_final(array $p, bool $ignoreDates = false): int
{
    $prix = (int) $p['prix'];
    if (!$ignoreDates && !promo_active($p)) {
        return $prix;
    }
    $v = (int) $p['promo_valeur'];
    if ($p['promo_type'] === 'pourcent') {
        return (int) round($prix * (100 - min($v, 99)) / 100, -2);
    }
    if ($p['promo_type'] === 'prix') {
        return min($v, $prix);
    }
    return $prix;
}

function remise_pct(array $p): int
{
    $prix = (int) $p['prix'];
    if ($prix <= 0) {
        return 0;
    }
    return (int) round(100 - prix_final($p) * 100 / $prix);
}

// ---------- Pièces ----------

function piece_image(array $p): string
{
    if (!empty($p['image']) && is_file(UPLOAD_DIR . '/' . $p['image'])) {
        return url('uploads/' . rawurlencode($p['image']));
    }
    return url('assets/img/piece-defaut.svg');
}

function get_piece(int $id, bool $actifSeulement = true): ?array
{
    $sql = 'SELECT * FROM pieces WHERE id = ?' . ($actifSeulement ? ' AND actif = 1' : '');
    $stmt = db()->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

// ---------- Sécurité (CSRF) ----------

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Session expirée. Veuillez recharger la page.');
    }
}

// ---------- Messages flash ----------

function flash(string $message, string $type = 'succes'): void
{
    $_SESSION['flash'][] = ['message' => $message, 'type' => $type];
}

function flashes(): string
{
    $html = '';
    foreach ($_SESSION['flash'] ?? [] as $f) {
        $html .= '<div class="alerte alerte-' . e($f['type']) . '">' . e($f['message']) . '</div>';
    }
    unset($_SESSION['flash']);
    return $html;
}

// ---------- Panier (stocké en session) ----------

function panier(): array
{
    return $_SESSION['panier'] ?? [];
}

function panier_nb(): int
{
    return array_sum(panier());
}

function panier_set(int $id, int $qte): void
{
    if ($qte <= 0) {
        unset($_SESSION['panier'][$id]);
    } else {
        $_SESSION['panier'][$id] = min($qte, 99);
    }
}

/** Retourne les lignes du panier avec les pièces et le total. */
function panier_detail(): array
{
    $lignes = [];
    $total = 0;
    foreach (panier() as $id => $qte) {
        $p = get_piece((int) $id);
        if (!$p) {
            continue;
        }
        $pu = prix_final($p);
        $lignes[] = ['piece' => $p, 'quantite' => $qte, 'prix_unitaire' => $pu, 'sous_total' => $pu * $qte];
        $total += $pu * $qte;
    }
    return ['lignes' => $lignes, 'total' => $total];
}

// ---------- Administration ----------

function admin_connecte(): bool
{
    return !empty($_SESSION['admin_id']);
}

function exiger_admin(): void
{
    if (!admin_connecte()) {
        redirect('admin/login.php');
    }
}

/** Enregistre une image envoyée et retourne son nom de fichier, ou null. */
function enregistrer_image(array $fichier): ?string
{
    if (($fichier['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($fichier['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Erreur lors de l’envoi de l’image.');
    }
    if ($fichier['size'] > UPLOAD_MAX_BYTES) {
        throw new RuntimeException('Image trop lourde (5 Mo maximum).');
    }
    $info = @getimagesize($fichier['tmp_name']);
    $extensions = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($extensions[$info[2]])) {
        throw new RuntimeException('Format d’image non accepté (JPG, PNG ou WEBP).');
    }
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0775, true);
    }
    $nom = 'piece-' . bin2hex(random_bytes(8)) . '.' . $extensions[$info[2]];
    if (!move_uploaded_file($fichier['tmp_name'], UPLOAD_DIR . '/' . $nom)) {
        throw new RuntimeException('Impossible d’enregistrer l’image.');
    }
    return $nom;
}

function supprimer_image(?string $nom): void
{
    if ($nom && preg_match('/^piece-[a-f0-9]+\.(jpg|png|webp)$/', $nom) && is_file(UPLOAD_DIR . '/' . $nom)) {
        unlink(UPLOAD_DIR . '/' . $nom);
    }
}

const STATUTS_COMMANDE = [
    'nouvelle' => 'Nouvelle',
    'confirmee' => 'Confirmée',
    'prete' => 'Prête au retrait',
    'livree' => 'Livrée / retirée',
    'annulee' => 'Annulée',
];

const STATUTS_RDV = [
    'nouveau' => 'Nouveau',
    'confirme' => 'Confirmé',
    'termine' => 'Terminé',
    'annule' => 'Annulé',
];

// ---------- Adresses propres (SEO) ----------

/** Transforme un texte en morceau d'adresse : « Électricité » → « electricite ». */
function slug(string $texte): string
{
    $t = strtr($texte, ['’' => '-', "'" => '-', '&' => 'et']);
    $t = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $t) ?: $t;
    $t = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $t));
    return trim($t, '-');
}

/** Retrouve la valeur d'une liste (marques, catégories) à partir de son slug. */
function depuis_slug(string $slug, array $liste): ?string
{
    foreach ($liste as $v) {
        if (slug($v) === $slug) {
            return $v;
        }
    }
    return null;
}

function lien_piece(array $p): string
{
    return url('piece/' . (int) $p['id'] . '-' . slug($p['nom']));
}

function lien_marque(string $m): string
{
    return url('marque/' . slug($m));
}

function lien_categorie(string $c): string
{
    return url('categorie/' . slug($c));
}

/** Adresse du catalogue la plus courte possible pour ces filtres. */
function lien_catalogue(array $f = []): string
{
    $f = array_filter(array_intersect_key($f, array_flip(['q', 'marque', 'categorie', 'promo', 'nouveau', 'tri'])),
        fn($v) => $v !== '' && $v !== null && $v !== '0');
    if (($f['tri'] ?? '') === 'recent') {
        unset($f['tri']);
    }
    $cles = array_keys($f);
    if ($cles === ['marque'] && in_array($f['marque'], MARQUES, true)) return lien_marque($f['marque']);
    if ($cles === ['categorie'] && in_array($f['categorie'], CATEGORIES, true)) return lien_categorie($f['categorie']);
    if ($cles === ['promo']) return url('promotions');
    if ($cles === ['nouveau']) return url('arrivages');
    return url('catalogue' . ($f ? '?' . http_build_query($f) : ''));
}

/** Adresse complète (avec https://coreeauto.mg) pour les balises SEO et le sitemap. */
function url_absolue(string $chemin): string
{
    $hote = preg_replace('/^www\./i', '', $_SERVER['HTTP_HOST'] ?? 'coreeauto.mg');
    // En ligne, le site est toujours servi en https (voir .htaccess) ; en local, on garde http.
    $local = PHP_SAPI === 'cli-server' || preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/', $hote);
    return ($local ? 'http' : 'https') . '://' . $hote . $chemin;
}

// ---------- Réglages du site (modifiables dans l'admin) ----------

/** Lit un réglage enregistré en base ; crée la table au premier usage. */
function reglage(string $cle, string $defaut = ''): string
{
    static $cache = null;
    if ($cache === null) {
        try {
            $cache = db()->query('SELECT cle, valeur FROM reglages')->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (PDOException $e) {
            db()->exec('CREATE TABLE IF NOT EXISTS reglages (cle VARCHAR(50) PRIMARY KEY, valeur TEXT NOT NULL)'
                . (DB_DRIVER === 'mysql' ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' : ''));
            $cache = [];
        }
    }
    return $cache[$cle] ?? $defaut;
}

function enregistrer_reglage(string $cle, string $valeur): void
{
    reglage($cle); // s'assure que la table existe
    $pdo = db();
    $pdo->prepare('DELETE FROM reglages WHERE cle = ?')->execute([$cle]);
    $pdo->prepare('INSERT INTO reglages (cle, valeur) VALUES (?, ?)')->execute([$cle, $valeur]);
}

const MODES_PAGE_PROMOS = [
    'auto' => 'Automatique : visible seulement s’il y a des promotions en cours',
    'afficher' => 'Toujours afficher',
    'masquer' => 'Masquer',
];

/** Nombre de pièces en ligne avec une promotion en cours aujourd'hui. */
function nb_promos_actives(): int
{
    static $nb = null;
    return $nb ??= (int) db()->query('SELECT COUNT(*) FROM pieces WHERE actif = 1 AND ' . promo_active_sql())->fetchColumn();
}

/** La page Promotions (et ses liens) doit-elle apparaître sur le site public ? */
function promotions_visibles(): bool
{
    return match (reglage('page_promotions', 'auto')) {
        'afficher' => true,
        'masquer' => false,
        default => nb_promos_actives() > 0,
    };
}
