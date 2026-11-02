<?php
// Envoi des e-mails du site (commandes, rendez-vous) par SMTP authentifié.
// Les identifiants sont dans includes/mail-config.php, présent uniquement sur le serveur
// (jamais dans le dépôt Git). Sans ce fichier, aucun e-mail n'est envoyé et le site continue de fonctionner.
//
// mail-config.php définit :
//   SMTP_HOST, SMTP_PORT (587), SMTP_SECURE ('tls' = STARTTLS, 'ssl' ou 'none'),
//   SMTP_USER, SMTP_PASS, MAIL_FROM, MAIL_TO (destinataire des notifications, par défaut MAIL_FROM)

if (is_file(__DIR__ . '/mail-config.php')) {
    require_once __DIR__ . '/mail-config.php';
}

function mail_configure(): bool
{
    return defined('SMTP_HOST') && defined('SMTP_USER') && defined('SMTP_PASS') && SMTP_PASS !== '';
}

/** Encode un en-tête (sujet, nom) en UTF-8 si besoin. */
function mail_entete(string $texte): string
{
    return preg_match('/[^\x20-\x7E]/', $texte) ? '=?UTF-8?B?' . base64_encode($texte) . '?=' : $texte;
}

/**
 * Envoie un e-mail texte. Retourne true si le serveur l'a accepté.
 * Ne lève jamais d'exception : une erreur est seulement journalisée, pour ne pas bloquer une commande.
 */
function envoyer_mail(string $destinataire, string $sujet, string $texte, ?string $repondreA = null, ?string &$erreur = null): bool
{
    $erreur = null;
    if (!mail_configure()) {
        $erreur = 'Envoi d’e-mails non configuré (includes/mail-config.php absent).';
        return false;
    }
    if (!filter_var($destinataire, FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Adresse du destinataire invalide.';
        return false;
    }
    $de = defined('MAIL_FROM') ? MAIL_FROM : SMTP_USER;
    $port = defined('SMTP_PORT') ? (int) SMTP_PORT : 587;
    $securite = defined('SMTP_SECURE') ? SMTP_SECURE : 'tls';

    try {
        $prefixe = $securite === 'ssl' ? 'ssl://' : 'tcp://';
        $contexte = stream_context_create(['ssl' => ['peer_name' => SMTP_HOST, 'SNI_enabled' => true]]);
        $sock = @stream_socket_client($prefixe . SMTP_HOST . ':' . $port, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $contexte);
        if (!$sock) {
            throw new RuntimeException("Connexion impossible à " . SMTP_HOST . ":$port ($errstr)");
        }
        stream_set_timeout($sock, 20);

        $lire = function () use ($sock): array {
            $reponse = '';
            while (($ligne = fgets($sock, 515)) !== false) {
                $reponse .= $ligne;
                if (strlen($ligne) < 4 || $ligne[3] !== '-') {
                    break;
                }
            }
            return [(int) substr($reponse, 0, 3), trim($reponse)];
        };
        $cmd = function (string $commande, array $attendus, string $journal = null) use ($sock, $lire): string {
            fwrite($sock, $commande . "\r\n");
            [$code, $texte] = $lire();
            if (!in_array($code, $attendus, true)) {
                throw new RuntimeException(($journal ?? strtok($commande, ' ')) . " refusé : $texte");
            }
            return $texte;
        };

        [$code, $accueil] = $lire();
        if ($code !== 220) {
            throw new RuntimeException("Serveur SMTP : $accueil");
        }
        $hoteLocal = preg_replace('/[^A-Za-z0-9.-]/', '', $_SERVER['SERVER_NAME'] ?? 'coreeauto.mg') ?: 'coreeauto.mg';
        $cmd("EHLO $hoteLocal", [250]);
        if ($securite === 'tls') {
            $cmd('STARTTLS', [220]);
            if (!stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                throw new RuntimeException('Échec du chiffrement TLS.');
            }
            $cmd("EHLO $hoteLocal", [250]);
        }
        $cmd('AUTH LOGIN', [334]);
        $cmd(base64_encode(SMTP_USER), [334], 'Identifiant');
        $cmd(base64_encode(SMTP_PASS), [235], 'Mot de passe');
        $cmd('MAIL FROM:<' . $de . '>', [250]);
        $cmd('RCPT TO:<' . $destinataire . '>', [250, 251]);
        $cmd('DATA', [354]);

        $entetes = [
            'Date: ' . date('r'),
            'From: ' . mail_entete(SITE_NAME) . ' <' . $de . '>',
            'To: <' . $destinataire . '>',
            'Subject: ' . mail_entete($sujet),
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . substr(strrchr($de, '@'), 1) . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: quoted-printable',
        ];
        if ($repondreA && filter_var($repondreA, FILTER_VALIDATE_EMAIL)) {
            $entetes[] = 'Reply-To: <' . $repondreA . '>';
        }
        $corps = quoted_printable_encode(str_replace(["\r\n", "\r", "\n"], ["\n", "\n", "\r\n"], $texte));
        $corps = preg_replace('/^\./m', '..', $corps); // « dot-stuffing » SMTP
        $cmd(implode("\r\n", $entetes) . "\r\n\r\n" . $corps . "\r\n.", [250], 'Message');
        fwrite($sock, "QUIT\r\n");
        fclose($sock);
        return true;
    } catch (Throwable $e) {
        $erreur = $e->getMessage();
        error_log('[Corée Auto] E-mail non envoyé à ' . $destinataire . ' : ' . $erreur);
        if (isset($sock) && is_resource($sock)) {
            fclose($sock);
        }
        return false;
    }
}

/** Adresse qui reçoit les notifications du site (commandes, rendez-vous). */
function mail_boutique(): string
{
    return defined('MAIL_TO') ? MAIL_TO : (defined('MAIL_FROM') ? MAIL_FROM : SITE_EMAIL);
}
