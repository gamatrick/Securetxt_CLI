<?php

namespace Securetxt;

use RuntimeException;

/**
 * Export chiffré : un seul fichier .stx qui contient des notes (et leurs dossiers),
 * protégé par un mot de passe choisi au moment de l'export.
 *
 * Format du fichier :
 *   ligne 1  "SECURETXT-EXPORT"
 *   ligne 2  en-tête JSON (format, version, sel et coût Argon2id) — public
 *   reste    nonce + données chiffrées (XChaCha20-Poly1305), l'en-tête servant de
 *            « données associées » : le modifier rend le fichier illisible.
 *
 * Les données chiffrées sont un JSON : {"kind", "base", "notes": [{"path","text"}], "folders": [...]}
 * ("base" = dossier d'origine, pour réécrire les liens [[chemin/complet]] à l'import).
 * Rien n'est jamais écrit en clair sur le disque : tout se fait en mémoire.
 */
final class Package
{
    public const EXT = 'stx';
    public const MIN_PASSWORD = 8;
    private const MAGIC = "SECURETXT-EXPORT\n";
    private const FORMAT = 'securetxt-export';
    /** Plus coûteux que le coffre : le fichier voyage et peut être attaqué hors ligne. */
    private const OPS = SODIUM_CRYPTO_PWHASH_OPSLIMIT_MODERATE;
    private const MEM = SODIUM_CRYPTO_PWHASH_MEMLIMIT_MODERATE;
    /** Bornes acceptées à la lecture (un fichier piégé ne doit pas bloquer la machine). */
    private const MAX_OPS = 10;
    private const MAX_MEM = 1024 * 1024 * 1024;
    private const MAX_FILE = 200 * 1024 * 1024;

    /** Le fichier commence-t-il par l'en-tête d'un export chiffré Securetxt ? */
    public static function isPackage(string $file): bool
    {
        if (!is_file($file)) {
            return false;
        }
        $h = @fopen($file, 'rb');
        if ($h === false) {
            return false;
        }
        $start = (string) fread($h, strlen(self::MAGIC));
        fclose($h);
        return $start === self::MAGIC;
    }

    /** Nom de fichier proposé : « Idées-2026-11-04.stx ». */
    public static function fileName(string $label): string
    {
        $s = rtrim(preg_replace('/[<>:"\/\\\\|?*\x00-\x1F]/', '_', $label), '. ');
        return ($s === '' ? 'export' : $s) . '-' . date('Y-m-d') . '.' . self::EXT;
    }

    /**
     * Chiffre les notes $ids dans un fichier .stx du dossier $destDir.
     * $base = préfixe de dossier retiré des chemins (comme pour l'export en clair).
     * @return array{0:int,1:string} [notes exportées, chemin du fichier créé]
     */
    public static function export(Vault $v, array $ids, string $base, string $kind, string $label,
                                  string $destDir, string $password): array
    {
        if (mb_strlen($password) < self::MIN_PASSWORD) {
            throw new RuntimeException('Mot de passe trop court (' . self::MIN_PASSWORD . ' caractères minimum).');
        }
        $notes = [];
        foreach ($ids as $id) {
            $path = $v->path($id);
            if ($path !== null) {
                $notes[] = ['path' => self::relative($path, $base), 'text' => $v->read($id)];
            }
        }
        // dossiers vides (créés à la main) qui font partie de la sélection
        $folders = [];
        foreach ($v->explicitFolders() as $f) {
            if ($kind === 'vault' || ($kind === 'folder' && ($f === $label || strncmp($f, $label . '/', strlen($label) + 1) === 0))) {
                $folders[] = self::relative($f, $base);
            }
        }
        $plain = json_encode(['kind' => $kind, 'base' => $base, 'notes' => $notes, 'folders' => $folders],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $salt = random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);
        $header = json_encode([
            'format' => self::FORMAT,
            'v' => 1,
            'kdf' => 'argon2id13',
            'salt' => base64_encode($salt),
            'ops' => self::OPS,
            'mem' => self::MEM,
            'cipher' => 'xchacha20poly1305',
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $key = self::derive($password, $salt, self::OPS, self::MEM);
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $sealed = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($plain, self::MAGIC . $header, $nonce, $key);
        sodium_memzero($key);
        sodium_memzero($plain);

        if (!is_dir($destDir) && !@mkdir($destDir, 0700, true)) {
            throw new RuntimeException('Impossible de créer le dossier : ' . $destDir);
        }
        $name = self::fileName($kind === 'vault' ? 'Securetxt-coffre' : Vault::base($label));
        $target = rtrim($destDir, '/\\') . DIRECTORY_SEPARATOR . $name;
        for ($i = 2; file_exists($target); $i++) {
            $target = rtrim($destDir, '/\\') . DIRECTORY_SEPARATOR . substr($name, 0, -4) . ' (' . $i . ').' . self::EXT;
        }
        $tmp = $target . '.tmp';
        if (@file_put_contents($tmp, self::MAGIC . $header . "\n" . $nonce . $sealed, LOCK_EX) === false
            || !@rename($tmp, $target)) {
            @unlink($tmp);
            throw new RuntimeException('Écriture impossible : ' . $target);
        }
        @chmod($target, 0600);
        return [count($notes), $target];
    }

    /**
     * Déchiffre un fichier .stx, puis vérifie tout son contenu.
     * Rien n'est inséré dans le coffre ici : voir insert().
     * @return array{kind:string,notes:list<array{path:string,text:string}>,folders:list<string>}
     */
    public static function open(string $file, string $password): array
    {
        $size = @filesize($file);
        if ($size === false || $size > self::MAX_FILE) {
            throw new RuntimeException('Fichier illisible ou trop gros : ' . $file);
        }
        $raw = (string) @file_get_contents($file);
        if (strncmp($raw, self::MAGIC, strlen(self::MAGIC)) !== 0) {
            throw new RuntimeException('Ce n\'est pas un export chiffré Securetxt.');
        }
        $eol = strpos($raw, "\n", strlen(self::MAGIC));
        if ($eol === false) {
            throw new RuntimeException('Fichier endommagé (en-tête incomplet).');
        }
        $header = substr($raw, strlen(self::MAGIC), $eol - strlen(self::MAGIC));
        $h = json_decode($header, true);
        if (!is_array($h) || ($h['format'] ?? '') !== self::FORMAT) {
            throw new RuntimeException('Fichier endommagé (en-tête illisible).');
        }
        if ((int) ($h['v'] ?? 0) !== 1 || ($h['kdf'] ?? '') !== 'argon2id13' || ($h['cipher'] ?? '') !== 'xchacha20poly1305') {
            throw new RuntimeException('Export créé par une version plus récente de Securetxt : mets l\'application à jour.');
        }
        $salt = base64_decode((string) ($h['salt'] ?? ''), true);
        $ops = (int) ($h['ops'] ?? 0);
        $mem = (int) ($h['mem'] ?? 0);
        if ($salt === false || strlen($salt) !== SODIUM_CRYPTO_PWHASH_SALTBYTES
            || $ops < 1 || $ops > self::MAX_OPS || $mem < 8192 || $mem > self::MAX_MEM) {
            throw new RuntimeException('Fichier endommagé (paramètres invalides).');
        }
        $body = substr($raw, $eol + 1);
        $nlen = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;
        if (strlen($body) <= $nlen) {
            throw new RuntimeException('Fichier endommagé (vide).');
        }
        $key = self::derive($password, $salt, $ops, $mem);
        $plain = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(substr($body, $nlen), self::MAGIC . $header,
            substr($body, 0, $nlen), $key);
        sodium_memzero($key);
        if ($plain === false) {
            throw new RuntimeException('Mot de passe incorrect ou fichier endommagé.');
        }
        $data = json_decode($plain, true);
        sodium_memzero($plain);
        if (!is_array($data) || !is_array($data['notes'] ?? null) || !is_array($data['folders'] ?? [])) {
            throw new RuntimeException('Fichier endommagé (contenu illisible).');
        }
        // vérification complète AVANT toute insertion (tout ou rien)
        $out = ['kind' => (string) ($data['kind'] ?? 'folder'), 'base' => Vault::normalize((string) ($data['base'] ?? '')),
            'notes' => [], 'folders' => []];
        foreach ($data['notes'] as $n) {
            $path = Vault::normalize((string) ($n['path'] ?? ''));
            if (!is_string($n['text'] ?? null) || Vault::validate($path) !== null) {
                throw new RuntimeException('Fichier endommagé (note invalide).');
            }
            $out['notes'][] = ['path' => $path, 'text' => $n['text']];
        }
        foreach ($data['folders'] ?? [] as $f) {
            $f = Vault::normalize((string) $f);
            if (Vault::validate($f) !== null) {
                throw new RuntimeException('Fichier endommagé (dossier invalide).');
            }
            $out['folders'][] = $f;
        }
        return $out;
    }

    /**
     * Range le contenu déchiffré dans le dossier $folder du coffre (chiffré avec la clé du coffre).
     * Un dossier exporté qui existe déjà devient « Nom (2) » pour ne rien mélanger.
     * @return array{0:int,1:?string} [notes importées, chemin de la première note]
     */
    public static function insert(Vault $v, array $data, string $folder): array
    {
        $prefix = fn (string $p): string => $folder === '' ? $p : $folder . '/' . $p;
        // nom du dossier racine de l'export (ex. « Idées ») : renommé s'il existe déjà ici
        $rename = [];
        if ($data['kind'] === 'folder') {
            $roots = [];
            foreach ($data['notes'] as $n) {
                $roots[explode('/', $n['path'])[0]] = true;
            }
            foreach ($data['folders'] as $f) {
                $roots[explode('/', $f)[0]] = true;
            }
            foreach (array_keys($roots) as $r) {
                $r = (string) $r;
                $new = $r;
                for ($i = 2; self::folderExists($v, $prefix($new)); $i++) {
                    $new = $r . ' (' . $i . ')';
                }
                $rename[$r] = $new;
            }
        }
        $map = function (string $p) use ($rename, $prefix): string {
            $parts = explode('/', $p, 2);
            if (isset($rename[$parts[0]])) {
                $parts[0] = $rename[$parts[0]];
            }
            return $prefix(implode('/', $parts));
        };
        // validation des chemins finaux avant d'écrire quoi que ce soit
        $plan = [];
        $moved = [];   // chemin d'origine complet (replié) => nouveau chemin
        $base = $data['base'] ?? '';
        foreach ($data['notes'] as $n) {
            $path = $map($n['path']);
            if (Vault::validate($path) !== null) {
                throw new RuntimeException('Chemin trop long ou invalide après import : ' . $path);
            }
            $plan[] = [$path, $n['text']];
            $moved[Text::fold($base === '' ? $n['path'] : $base . '/' . $n['path'])] = $path;
        }
        // les liens écrits avec un chemin complet ([[Idées/sous/note]]) suivent les notes déplacées ;
        // les liens relatifs ([[note]], [[sous/note]]) restent valables tels quels
        foreach ($plan as $k => [$path, $text]) {
            $plan[$k][1] = preg_replace_callback(Vault::LINK_RE, function ($m) use ($moved): string {
                $t = Vault::normalize($m[1]);
                $new = $moved[Text::fold($t)] ?? null;
                return ($new === null || $new === $t) ? $m[0] : '[[' . $new . ']]';
            }, $text);
        }
        $made = 0;
        $first = null;
        $v->beginBatch();
        try {
            foreach ($data['folders'] as $f) {
                $v->createFolder($map($f));
            }
            foreach ($plan as [$path, $text]) {
                $path = $v->uniquePath($path);
                $v->createNote($path, $text);
                $first ??= $path;
                $made++;
            }
        } finally {
            $v->endBatch();
        }
        return [$made, $first];
    }

    private static function folderExists(Vault $v, string $f): bool
    {
        if ($v->findByPath($f) !== null || in_array($f, $v->explicitFolders(), true)) {
            return true;
        }
        $pre = $f . '/';
        foreach ($v->notes() as $p) {
            if (strncmp($p, $pre, strlen($pre)) === 0) {
                return true;
            }
        }
        return false;
    }

    private static function relative(string $path, string $base): string
    {
        return $base === '' ? $path : substr($path, strlen($base) + 1);
    }

    private static function derive(string $pw, string $salt, int $ops, int $mem): string
    {
        return sodium_crypto_pwhash(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES, $pw, $salt, $ops, $mem,
            SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13);
    }
}
