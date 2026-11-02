<?php
// Connexion à la base de données.
// - MySQL / MariaDB si includes/db-config.php existe et choisit DB_DRIVER = 'mysql' (hébergeur)
// - SQLite (fichier data/coreeauto.sqlite) sinon, pratique pour tester sur son PC
// Les tables sont créées automatiquement au premier lancement.

if (is_file(__DIR__ . '/db-config.php')) {
    require __DIR__ . '/db-config.php';
}
if (!defined('DB_DRIVER')) {
    define('DB_DRIVER', 'sqlite');
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    if (DB_DRIVER === 'mysql') {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        $pdo->exec("SET time_zone = '+03:00'"); // heure de Madagascar
    } else {
        if (!is_dir(dirname(DB_PATH))) {
            mkdir(dirname(DB_PATH), 0775, true);
        }
        $pdo = new PDO('sqlite:' . DB_PATH, null, null, $options);
        $pdo->exec('PRAGMA foreign_keys = ON');
    }
    installer_si_besoin($pdo);
    return $pdo;
}

/** Crée les tables manquantes, le compte admin par défaut et (en SQLite) les pièces de démonstration. */
function installer_si_besoin(PDO $pdo): void
{
    try {
        $pdo->query('SELECT 1 FROM rendez_vous LIMIT 1');
        migrer_admins($pdo);
        return; // la dernière table ajoutée existe : tout est en place
    } catch (PDOException $e) {
        // tables absentes : on les crée ci-dessous
    }
    foreach (DB_DRIVER === 'mysql' ? schema_mysql() : schema_sqlite() as $sql) {
        $pdo->exec($sql);
    }
    if ((int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() === 0) {
        // Compte administrateur par défaut : à changer dès la première connexion.
        $pdo->prepare('INSERT INTO admins (identifiant, mot_de_passe) VALUES (?, ?)')
            ->execute(['admin', password_hash(defined('ADMIN_MDP_INITIAL') ? ADMIN_MDP_INITIAL : 'coreeauto2026', PASSWORD_DEFAULT)]);
        if (DB_DRIVER !== 'mysql') {
            seed_demo($pdo); // pièces fictives uniquement pour les tests locaux
        }
    }
    migrer_admins($pdo);
}

/**
 * Ajoute aux comptes d'administration le nom, le rôle, les autorisations et l'état actif/désactivé.
 * Les comptes existant avant cette mise à jour deviennent administrateurs principaux.
 */
function migrer_admins(PDO $pdo): void
{
    try {
        $pdo->query('SELECT role FROM admins LIMIT 1');
        return;
    } catch (PDOException $e) {
        // colonnes absentes : on les ajoute
    }
    $colonnes = [
        "nom VARCHAR(100) NOT NULL DEFAULT ''",
        "permissions VARCHAR(255) NOT NULL DEFAULT ''",
        'actif INTEGER NOT NULL DEFAULT 1',
        "role VARCHAR(20) NOT NULL DEFAULT 'employe'", // en dernier : sa présence signale une migration terminée
    ];
    foreach ($colonnes as $colonne) {
        try {
            $pdo->exec('ALTER TABLE admins ADD COLUMN ' . $colonne);
        } catch (PDOException $e) {
            // colonne déjà ajoutée lors d'une tentative précédente
        }
    }
    $pdo->exec("UPDATE admins SET role = 'super'");
}

function schema_mysql(): array
{
    $fin = ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    return [
        "CREATE TABLE IF NOT EXISTS pieces (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            reference VARCHAR(100) NOT NULL,
            nom VARCHAR(255) NOT NULL,
            description TEXT,
            marque VARCHAR(50) NOT NULL DEFAULT '',
            modeles VARCHAR(255) NOT NULL DEFAULT '',
            categorie VARCHAR(50) NOT NULL DEFAULT '',
            prix INT UNSIGNED NOT NULL DEFAULT 0,
            stock INT NOT NULL DEFAULT 0,
            image VARCHAR(100) NULL,
            promo_type VARCHAR(10) NULL,
            promo_valeur INT UNSIGNED NULL,
            promo_debut DATE NULL,
            promo_fin DATE NULL,
            nouveau TINYINT(1) NOT NULL DEFAULT 0,
            actif TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_pieces_marque (marque),
            INDEX idx_pieces_categorie (categorie)
        )$fin",
        "CREATE TABLE IF NOT EXISTS commandes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(150) NOT NULL,
            telephone VARCHAR(30) NOT NULL,
            email VARCHAR(150) NOT NULL DEFAULT '',
            magasin VARCHAR(100) NOT NULL DEFAULT '',
            message TEXT,
            total INT UNSIGNED NOT NULL DEFAULT 0,
            statut VARCHAR(20) NOT NULL DEFAULT 'nouvelle',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )$fin",
        "CREATE TABLE IF NOT EXISTS commande_lignes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            commande_id INT UNSIGNED NOT NULL,
            piece_id INT UNSIGNED NULL,
            reference VARCHAR(100),
            nom VARCHAR(255),
            prix_unitaire INT UNSIGNED NOT NULL,
            quantite INT UNSIGNED NOT NULL,
            CONSTRAINT fk_lignes_commande FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE
        )$fin",
        "CREATE TABLE IF NOT EXISTS admins (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            identifiant VARCHAR(50) NOT NULL UNIQUE,
            mot_de_passe VARCHAR(255) NOT NULL
        )$fin",
        "CREATE TABLE IF NOT EXISTS rendez_vous (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(150) NOT NULL,
            telephone VARCHAR(30) NOT NULL,
            vehicule VARCHAR(150) NOT NULL DEFAULT '',
            service VARCHAR(100) NOT NULL DEFAULT '',
            magasin VARCHAR(100) NOT NULL DEFAULT '',
            date_souhaitee DATE NULL,
            message TEXT,
            statut VARCHAR(20) NOT NULL DEFAULT 'nouveau',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )$fin",
    ];
}

function schema_sqlite(): array
{
    $maintenant = "(datetime('now','localtime'))";
    return [
        "CREATE TABLE IF NOT EXISTS pieces (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            reference TEXT NOT NULL,
            nom TEXT NOT NULL,
            description TEXT DEFAULT '',
            marque TEXT DEFAULT '',
            modeles TEXT DEFAULT '',
            categorie TEXT DEFAULT '',
            prix INTEGER NOT NULL DEFAULT 0,
            stock INTEGER NOT NULL DEFAULT 0,
            image TEXT DEFAULT NULL,
            promo_type TEXT DEFAULT NULL,
            promo_valeur INTEGER DEFAULT NULL,
            promo_debut TEXT DEFAULT NULL,
            promo_fin TEXT DEFAULT NULL,
            nouveau INTEGER NOT NULL DEFAULT 0,
            actif INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL DEFAULT $maintenant,
            updated_at TEXT NOT NULL DEFAULT $maintenant
        )",
        'CREATE INDEX IF NOT EXISTS idx_pieces_marque ON pieces(marque)',
        'CREATE INDEX IF NOT EXISTS idx_pieces_categorie ON pieces(categorie)',
        "CREATE TABLE IF NOT EXISTS commandes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nom TEXT NOT NULL,
            telephone TEXT NOT NULL,
            email TEXT DEFAULT '',
            magasin TEXT DEFAULT '',
            message TEXT DEFAULT '',
            total INTEGER NOT NULL DEFAULT 0,
            statut TEXT NOT NULL DEFAULT 'nouvelle',
            created_at TEXT NOT NULL DEFAULT $maintenant
        )",
        'CREATE TABLE IF NOT EXISTS commande_lignes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            commande_id INTEGER NOT NULL REFERENCES commandes(id) ON DELETE CASCADE,
            piece_id INTEGER,
            reference TEXT,
            nom TEXT,
            prix_unitaire INTEGER NOT NULL,
            quantite INTEGER NOT NULL
        )',
        'CREATE TABLE IF NOT EXISTS admins (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            identifiant TEXT NOT NULL UNIQUE,
            mot_de_passe TEXT NOT NULL
        )',
        "CREATE TABLE IF NOT EXISTS rendez_vous (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nom TEXT NOT NULL,
            telephone TEXT NOT NULL,
            vehicule TEXT DEFAULT '',
            service TEXT DEFAULT '',
            magasin TEXT DEFAULT '',
            date_souhaitee TEXT DEFAULT NULL,
            message TEXT DEFAULT '',
            statut TEXT NOT NULL DEFAULT 'nouveau',
            created_at TEXT NOT NULL DEFAULT $maintenant
        )",
    ];
}

function seed_demo(PDO $pdo): void
{
    $today = date('Y-m-d');
    $in30 = date('Y-m-d', strtotime('+30 days'));
    $demo = [
        ['KIA-58101-2PA70', 'Plaquettes de frein avant', 'Jeu de 4 plaquettes de frein avant, qualité d’origine.', 'Kia', 'Sorento, Sportage', 'Freinage', 145000, 12, 'pourcent', 15, 1],
        ['HY-49500-2B000', 'Cardan avant gauche', 'Transmission avant complète avec soufflets.', 'Hyundai', 'Santa Fe, Tucson', 'Transmission', 620000, 4, null, null, 1],
        ['HY-58510-4H000', 'Maître-cylindre de frein', 'Maître-cylindre avec réservoir de liquide de frein.', 'Hyundai', 'H1, Starex', 'Freinage', 380000, 6, 'prix', 320000, 1],
        ['SY-58305-08000', 'Mâchoires de frein arrière', 'Jeu de mâchoires pour freins à tambour.', 'SsangYong', 'Rexton, Kyron', 'Freinage', 165000, 10, null, null, 0],
        ['KIA-54660-2P000', 'Amortisseur avant', 'Amortisseur hydraulique avant, côté droit.', 'Kia', 'Sorento', 'Suspension', 410000, 5, 'pourcent', 10, 0],
        ['HY-56820-4H000', 'Rotule de direction', 'Biellette et rotule de direction avec écrou.', 'Hyundai', 'H1, Starex', 'Direction', 95000, 20, null, null, 0],
        ['HY-26300-35505', 'Filtre à huile', 'Filtre à huile d’origine pour moteurs essence.', 'Hyundai', 'Accent, i10, Elantra', 'Filtration', 18000, 60, null, null, 0],
        ['KIA-41100-3E000', 'Kit d’embrayage', 'Disque, mécanisme et butée d’embrayage.', 'Kia', 'Sorento, Carnival', 'Embrayage', 890000, 3, 'pourcent', 12, 1],
        ['SY-66510-08000', 'Bougies d’allumage (x4)', 'Lot de 4 bougies d’allumage iridium.', 'SsangYong', 'Korando, Tivoli', 'Électricité', 72000, 25, null, null, 0],
        ['DW-96591-450', 'Roulement de roue', 'Roulement de roue avant à aiguilles.', 'Daewoo', 'Matiz, Lanos', 'Transmission', 68000, 15, null, null, 1],
        ['HY-33800-4A000', 'Injecteur diesel', 'Injecteur common rail, testé sur banc.', 'Hyundai', 'H1, Terracan', 'Injection', 750000, 2, null, null, 0],
        ['KIA-25310-3E100', 'Radiateur moteur', 'Radiateur de refroidissement en aluminium.', 'Kia', 'Sorento', 'Refroidissement', 540000, 3, null, null, 0],
    ];
    $stmt = $pdo->prepare('INSERT INTO pieces
        (reference, nom, description, marque, modeles, categorie, prix, stock, promo_type, promo_valeur, promo_debut, promo_fin, nouveau)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
    foreach ($demo as $p) {
        $hasPromo = $p[8] !== null;
        $stmt->execute([
            $p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6], $p[7],
            $p[8], $p[9], $hasPromo ? $today : null, $hasPromo ? $in30 : null, $p[10],
        ]);
    }
}
