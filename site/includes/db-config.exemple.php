<?php
// Modèle de configuration de la base MySQL / MariaDB.
// Copiez ce fichier en « db-config.php » (même dossier) et remplissez vos identifiants.
// Sans db-config.php, le site utilise SQLite (data/coreeauto.sqlite).
// Ne partagez jamais db-config.php : il contient le mot de passe de la base.

// 'mysql' sur l'hébergeur. Avec « php -S » sur votre PC, on garde SQLite pour tester sans serveur MySQL.
define('DB_DRIVER', PHP_SAPI === 'cli-server' ? 'sqlite' : 'mysql');

define('DB_HOST', 'localhost');
define('DB_NAME', 'nom_de_la_base');
define('DB_USER', 'utilisateur');
define('DB_PASS', 'mot_de_passe');

// Mot de passe du compte « admin » créé à la première connexion à une base vide.
define('ADMIN_MDP_INITIAL', 'choisissez-un-mot-de-passe-solide');
