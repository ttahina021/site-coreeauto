<?php
declare(strict_types=1);

require __DIR__ . '/config.php';

ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
session_name('coreeauto');
session_start();

date_default_timezone_set('Indian/Antananarivo');

// Chemin de base du site (fonctionne aussi dans un sous-dossier, ex. localhost/coreeauto/)
// Sous Windows, dirname('/index.php') renvoie '\' : on normalise après dirname.
$script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$base = rtrim(str_replace('\\', '/', dirname($script)), '/');
if (str_ends_with($base, '/admin')) {
    $base = substr($base, 0, -strlen('/admin'));
}
if (!defined('BASE_URL')) { // le routeur peut l'avoir déjà défini
    define('BASE_URL', $base);
}

require __DIR__ . '/db.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/mail.php';
