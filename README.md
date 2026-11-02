# Site Corée Auto — coreeauto.mg

Site de vente de pièces détachées coréennes, avec espace administrateur.
Technologies : PHP 8, MySQL/MariaDB en ligne, SQLite en local (rien à installer).

## Lancer le site sur votre ordinateur

**Option 1, la plus simple (PHP installé) :** ouvrez un terminal dans le dossier `site` puis tapez

    php -S localhost:8000 router.php

et ouvrez http://localhost:8000 dans votre navigateur.

**Option 2 : Laragon ou XAMPP.** Copiez le dossier `site` dans `C:\laragon\www\coreeauto`
(ou `C:\xampp\htdocs\coreeauto`) puis ouvrez http://localhost/coreeauto

Au premier lancement, la base `data/coreeauto.sqlite` est créée automatiquement
avec 12 pièces de démonstration (à supprimer ou modifier depuis l’admin).

## Espace administrateur

Adresse : `/admin/` (ex. http://localhost:8000/admin/)

- Identifiant : `admin`
- Mot de passe : celui défini par `ADMIN_MDP_INITIAL` dans `includes/db-config.php` (en ligne),
  ou le mot de passe de démonstration en local. **À changer tout de suite** dans « Mon compte ».

Ce que l’admin peut faire :
- **Pièces** : ajouter, modifier, masquer, supprimer, avec photo, stock, marque, catégorie, modèles compatibles.
- **Promotions** : sur une pièce (remise en % ou prix promo fixe, avec dates de début et de fin),
  ou sur plusieurs pièces d’un coup (par marque et/ou catégorie).
  Les promotions démarrent et s’arrêtent toutes seules selon les dates.
- **Commandes** : liste des demandes envoyées depuis le panier, avec changement de statut.

## Personnaliser

Tout se règle dans `includes/config.php` : téléphones et adresses des magasins, horaires,
numéro WhatsApp, liste des marques et des catégories.

## Base de données

- **Sur l’hébergeur : MySQL / MariaDB.** Les identifiants sont dans `includes/db-config.php`
  (modèle : `includes/db-config.exemple.php`). Ne partagez jamais ce fichier.
  Les tables et le compte `admin` sont créés automatiquement au premier affichage du site.
- **Sur votre PC avec `php -S` : SQLite** (`data/coreeauto.sqlite`), avec des pièces de démonstration,
  pour tester sans installer MySQL.

## Mise en ligne (automatique)

Le code du site est dans le dossier `site/`. **Chaque push sur la branche `main`** lance
le workflow GitHub Actions `.github/workflows/deploy.yml` :

1. vérification de la syntaxe de tous les fichiers PHP ;
2. envoi en FTPS sur `web2.simafri.cloud`, dans `public_html/`, des seuls fichiers modifiés ;
3. contrôle que le site répond (accueil, catalogue, sitemap, robots.txt, redirection 301,
   `db-config.php` inaccessible).

On peut aussi relancer un déploiement à la main : onglet **Actions** > *Déploiement coreeauto.mg* > **Run workflow**.

Secrets à créer une fois dans GitHub (Settings > Secrets and variables > Actions) :

| Nom | Valeur |
|---|---|
| `FTP_USERNAME` | identifiant FTP de l’hébergement |
| `FTP_PASSWORD` | mot de passe FTP |
| `SMTP_PASSWORD` | mot de passe de la boîte contact@coreeauto.mg (envoi des e-mails du site) |

Optionnel : la variable `FTP_SERVER_DIR` (onglet *Variables*) si le dossier web n’est pas `public_html/`
pour ce compte FTP.

Avec `SMTP_PASSWORD`, le déploiement écrit `includes/mail-config.php` sur le serveur (jamais dans le dépôt).

Ne sont **jamais** dans le dépôt ni modifiés par le déploiement : `site/includes/db-config.php`
(identifiants de la base), `site/data/` (SQLite locale), les photos de `site/uploads/`, `LISEZMOI.md`.
Le déploiement ne supprime sur le serveur que les fichiers qu’il a lui-même envoyés puis retirés du dépôt.

Sauvegardez régulièrement la base (export phpMyAdmin) et le dossier `uploads/` du serveur.

## Structure

    site/index.php, catalogue.php, piece.php, panier.php, contact.php   pages publiques
    admin/                                                          espace administrateur
    includes/                                                       configuration, base de données, fonctions
    assets/                                                         CSS, JS, logo et images
    uploads/                                                        photos des pièces envoyées
    data/                                                           base SQLite (créée automatiquement)

## Adresses propres et référencement (SEO)

Les pages publiques ont des adresses courtes, sans « .php » :
`/catalogue`, `/promotions`, `/arrivages`, `/marque/kia`, `/categorie/freinage`,
`/piece/12-plaquettes-de-frein-avant`, `/panier`, `/atelier`, `/magasins`.
Les anciennes adresses en .php redirigent automatiquement (301) vers les nouvelles.

- `router.php` reçoit ces adresses (via `.htaccess` sur le serveur, ou `php -S localhost:8000 router.php` en local).
- `/sitemap.xml` est généré automatiquement à partir des pièces en ligne.
- `/robots.txt` interdit l'indexation de l'admin, du panier et des recherches.
- Chaque page a son titre, sa description, son adresse canonique, ses balises Open Graph,
  et des données structurées (magasin, produit, fil d'Ariane) pour Google.

Après la mise en ligne, déclarez `https://coreeauto.mg/sitemap.xml` dans Google Search Console.
