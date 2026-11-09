# Securetxt v0.4.0
 
Carnet de notes chiffrées pour le terminal, en PHP pur.
Aucun serveur, aucune dépendance externe, et une expérience clavier first (la souris est un bonus).

## Introduction

**Securetxt** est un coffre-fort de notes chiffrées pour le terminal. Il offre un endroit sûr et rapide d'accès pour ranger ce qui ne doit jamais traîner dans un simple fichier `.txt` :

- fichiers `.env` et variables d'environnement ;
- clés d'API, jetons et identifiants de test ;
- extraits de code ou de configuration sensibles ;
- procédures internes et notes techniques confidentielles.

Grâce aux **liens entre notes parentes et enfants**, Securetxt sert aussi de **wiki personnel**. Chaque projet peut être documenté en notes reliées entre elles, et la recherche dans tout le coffre (Ctrl+F) retrouve en quelques touches un détail oublié : une commande, un choix technique, une configuration.

### Pourquoi Securetxt ?

- Tout est chiffré, même les noms. Le contenu des notes comme leurs noms sont chiffrés. Sans le mot de passe, le coffre n'est qu'un ensemble de fichiers illisibles.
- 100 % local. Pas de serveur, pas de compte, pas de cloud : tes données ne quittent jamais ta machine.
- Rapide à l'usage. L'application se lance dans le terminal et s'utilise entièrement au clavier : pas besoin de quitter ton environnement de travail.
- Simple à installer. Pas de dépendance à installer : PHP et l'extension libsodium suffisent.
- Durable. Le format du coffre ne change pas d'une version à l'autre : tes notes restent lisibles après chaque mise à jour.

### À qui s'adresse-t-il ?

Aux développeurs, administrateurs système et à toute personne qui manipule des informations sensibles au quotidien. Securetxt remplace le fichier `secrets.txt` oublié sur le Bureau, les mots de passe notés dans un bloc-notes, ou la documentation de projet éparpillée dans plusieurs fichiers.

### Développement

Securetxt est développé par **gamatrick**, avec l'aide de **Claude** , en particulier pour la partie sécurité (chiffrement du coffre) et l'import / export des notes et des coffres.


---
## Sommaire
 
- [Fonctionnalités](#fonctionnalités)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Premiers pas](#premiers-pas)
- [Utilisation](#utilisation)
  - [Arborescence](#arborescence)
  - [Éditeur](#éditeur)
  - [Recherche](#recherche)
  - [Notes parentes et enfants](#notes-parentes-et-enfants)
  - [Souris](#souris)
  - [Import / export](#import--export)
- [Personnalisation](#personnalisation)
- [Sécurité et format du coffre](#sécurité-et-format-du-coffre)
- [Dépannage](#dépannage)
- [Mise à jour](#mise-à-jour)
- [Journal des versions](#journal-des-versions)
---
 
## Fonctionnalités
 
- **Chiffrement fort** : Argon2id + XSalsa20-Poly1305 (libsodium). Les noms des notes sont chiffrés eux aussi.
- **Notes organisées en dossiers**, avec des liens `[[note]]` qui créent une arborescence de notes parentes et enfants.
- **Recherche instantanée** dans les dossiers, les noms de notes et le contenu, sans tenir compte des majuscules ni des accents.
- **Éditeur complet** : sélection, copier / coller, annuler / rétablir, indentation, retour à la ligne automatique, numéros de ligne.
- **Entièrement personnalisable** : thèmes 256 couleurs, thèmes personnels avec logo ASCII, tous les raccourcis sont modifiables.
- - **Import / export** de fichiers texte, et **export chiffré** (`.stx`) pour transférer des notes d'un coffre à un autre en toute sécurité.
- **Windows et Linux**, avec création d'un raccourci sur le Bureau.
## Prérequis
 
| | |
|---|---|
| **PHP** | 8.5 |
| **Extensions** | `sodium`, `mbstring`, et `ffi` sous Windows |
| **Terminal** | Windows Terminal recommandé sous Windows ; Ghostty, kitty, foot ou WezTerm sous Linux |
 
Vérifier les extensions : `php -m`
 
## Installation
 
1. Télécharge la dernière archive `Securetxt-vX.Y.Z.zip` depuis la page **Releases** et décompresse-la.
2. Configure le lancement selon ton système.
### Windows
 
1. Ouvre `securetxt.bat` dans un éditeur de texte et indique le chemin de ton `php.exe` sur la ligne `PHP85=`.
2. Double-clique sur `securetxt.bat` pour lancer l'application.
3. *(Facultatif)* Double-clique sur `creer-raccourci.bat` (ou Paramètres > Créer un raccourci) pour obtenir une icône sur le Bureau. Le raccourci utilise Windows Terminal s'il est installé.
### Linux
 
```sh
chmod +x securetxt.sh creer-raccourci.sh
./securetxt.sh
# ou, avec un PHP précis :
PHP85=/chemin/vers/php ./securetxt.sh
```
 
*(Facultatif)* Raccourci dans le menu des applications (GNOME, KDE…) :
 
```sh
./creer-raccourci.sh             # crée le raccourci
./creer-raccourci.sh --supprimer # le retire
```
 
Securetxt apparaît alors dans la liste des applications (touche Super, puis « Securetxt »). Il s'ouvre dans Ghostty s'il est installé, sinon dans le premier terminal trouvé (Console, GNOME Terminal, kitty, foot…). Une icône est aussi posée sur le Bureau s'il existe (sous GNOME « Desktop Icons NG » nécessaire).
Si tu déplaces le dossier de Securetxt, relance le script.
 
## Premiers pas
 
1. Au premier lancement, Securetxt crée un coffre dans le dossier `coffre/` et te demande de choisir un mot de passe.
2. Une note de bienvenue explique les bases.
3. **N** crée une note, **Entrée** l'ouvre, **Échap** revient à l'arborescence. L'enregistrement est automatique en sortant d'une note.
> **Attention** :  Il n'existe aucun moyen de récupérer les notes si le mot de passe est perdu.
 
## Utilisation
 
### Arborescence
 
| Touche | Action |
|---|---|
| Entrée | ouvrir la note / déplier le dossier |
| N | nouvelle note |
| D | nouveau dossier |
| R | renommer |
| Suppr | effacer |
| I | importer des fichiers |
| e / E | exporter la sélection / tout le coffre |
| Ctrl+F | rechercher |
| P | paramètres |
| L | verrouiller le coffre |
 
### Éditeur
 
| Touche | Action |
|---|---|
| Ctrl+← / Ctrl+→ | mot précédent / suivant |
| Ctrl+↑ / Ctrl+↓ | ligne réelle précédente / suivante (↑ ↓ suivent les lignes affichées quand le texte revient à la ligne) |
| Maj+flèches, Maj+Début/Fin | sélectionner |
| Ctrl+Maj+← / → | sélectionner mot par mot |
| Ctrl+Maj+↑ / ↓ | sélectionner ligne entière par ligne entière |
| Ctrl+A | tout sélectionner |
| Ctrl+C / Ctrl+X / Ctrl+V | copier / couper / coller (sans sélection : la ligne entière) |
| Ctrl+Z / Ctrl+Y | annuler / rétablir |
| Ctrl+Retour arr. (ou +Maj) | effacer le mot à gauche |
| Ctrl+Suppr (ou +Maj) | effacer le mot à droite |
| Maj+Entrée | insérer une ligne vide au début de la ligne (le texte descend) |
| Ctrl+Entrée | nouvelle ligne en dessous, sans couper la ligne |
| Tab / Maj+Tab | indenter / désindenter (plusieurs lignes si sélection) |
| Ctrl+K | supprimer la ligne |
| Alt+Z | retour à la ligne automatique oui / non |
| Ctrl+S | enregistrer (aussi automatique en sortant) |
| Ctrl+O | ouvrir le `[[lien]]` sous le curseur (le crée s'il n'existe pas) |
| Ctrl+B | rétroliens (notes qui pointent vers celle-ci) |
| Ctrl+F | recherche dans tout le coffre |
| Échap | revenir à la note parente, puis à l'arborescence |
 
Tous les raccourcis se modifient dans **Ctrl+P > Raccourcis**.
 
### Recherche
 
Ctrl+F cherche dans tout le coffre, sans tenir compte des majuscules ni des accents. Les résultats sont classés ainsi :
 
1. Dossiers dont le nom correspond, marqués `(dossier)`. Entrée revient à l'arborescence, sur ce dossier ouvert.
2. Notes dont le nom correspond, marquées `(nom de la note)`. Seul le nom de la note compte, pas celui de son dossier.
3. Lignes des notes qui contiennent le texte cherché (`note:numéro de ligne`).
### Notes parentes et enfants
 
Une note qui contient un lien `[[...]]` devient le parent de la note visée.
 
```
[-] Idées/
   ▼  amélioration
        |- ▼ exempledeenfant1
               |- enfantdeenfant1
        |- mail
   ►  autre note
```
 
| Symbole / touche | Signification |
|---|---|
| `►` | la note a des enfants, cachés |
| `▼` | ses enfants sont affichés |
| `[+]` / `[-]` | réservés aux dossiers |
| `↺` | boucle (A → B → A) |
| Espace | afficher / cacher les enfants |
| → / ← | afficher les enfants / les cacher ou remonter à la note parente |
 
Règles d'affichage :
 
- Tout est fermé au lancement. En revenant d'une note, les branches qui y mènent s'ouvrent.
- Une note dont le parent est dans le même dossier n'apparaît que sous ce parent. Si le parent est dans un autre dossier, elle reste aussi listée dans son propre dossier.
- Une note liée par plusieurs notes apparaît sous chacune d'elles.
- Les images et les liens vers des notes pas encore créées ne sont pas affichés comme enfants.
- Les couleurs du symbole `►` / `▼` et des notes enfants se règlent séparément de celle des `[[liens]]` (Ctrl+P > Couleurs).
### Souris
 
- Arborescence : clic pour sélectionner, double-clic pour ouvrir, clic sur `[-]` / `[+]` pour plier, clic sur `►` / `▼` pour les enfants, molette pour défiler.
- Éditeur : clic pour placer le curseur, glisser pour sélectionner, double-clic pour un mot, triple-clic pour une ligne, Maj+clic pour étendre la sélection, Ctrl+clic pour ouvrir un `[[lien]]`.
- Barre du bas : chaque touche affichée est un bouton.
- Les numéros de ligne ne sont jamais copiés : la sélection ne porte que sur le texte.
### Import / export
 
- **I** importe un fichier ou un dossier entier (sous-dossiers compris) dans le dossier sélectionné. Tab complète le chemin ; on peut aussi glisser le fichier dans la fenêtre. Les fichiers non texte sont ignorés et l'encodage Windows (ANSI) est converti.
- **e** exporte la note ou le dossier sélectionné, **E** tout le coffre. Securetxt demande ensuite le format :
  - **C = chiffré** : un seul fichier `.stx` (ex. `Idées-2026-11-09.stx`), protégé par un mot de passe choisi pour l'export (8 caractères minimum, saisi deux fois). Le fichier ne révèle ni le nom, ni le nombre, ni la taille des notes.
  - **T = texte en clair** : des fichiers `.txt` non chiffrés.
- **I** sur un fichier `.stx` demande son mot de passe, vérifie tout en mémoire, puis range les notes dans le dossier sélectionné, chiffrées avec le mot de passe du coffre. Rien n'est importé si le mot de passe est faux ou le fichier abîmé. Un dossier qui existe déjà est importé sous « Nom (2) ».

> **Attention** : Les fichiers importés restent **en clair** sur le disque (supprime-les toi-même), l'export **T** n'est pas chiffré, et le mot de passe d'un `.stx` est **irrécupérable**.
 
## Personnalisation
 
Les paramètres s'ouvrent avec Ctrl+P (ou P dans l'arborescence) et s'enregistrent immédiatement dans `coffre/settings.json`.
 
| Réglage | Description |
|---|---|
| Thème | Smile Basic, Nuit, Ambre, Phosphore vert, Papier, plus tes thèmes enregistrés (marqués « perso ») |
| Couleurs | chaque élément (texte, liens, barres, sélection…) se règle séparément : ←/→ ±1, Maj+←/→ ±10, Entrée pour saisir une valeur de 0 à 255, R pour annuler |
| Enregistrer un thème | dans l'écran des couleurs, S enregistre les couleurs actuelles sous un nom ; même nom = mise à jour ; **Suppr** sur la ligne « Thème » le supprime |
| Logo du thème à la connexion | affiche le dessin ASCII du thème sur l'écran du mot de passe |
| Éditeur | retour à la ligne automatique, numéros de ligne, indentation automatique, largeur des tabulations |
| Souris | activée / désactivée |
| Raccourcis | Entrée remplace, + ajoute une touche, Suppr efface, Retour arrière rétablit la touche par défaut |
 
Changer de thème sans enregistrer perd les modifications en cours (une confirmation est demandée).
 
### Thèmes personnalisés (settings.json)
 
Les thèmes enregistrés sont rangés dans `"customThemes"` de `coffre/settings.json` :
 
```json
"Mon thème": {
  "base": "nuit",
  "logo": [
    "  /\\  ",
    " /  \\ ",
    "/____\\"
  ],
  "colors": { "text": [153, 234, false, false] }
}
```
 
| Clé | Rôle |
|---|---|
| `base` | thème intégré qui fournit les couleurs manquantes : `smile`, `nuit`, `ambre`, `phosphore`, `papier` |
| `logo` | *(facultatif)* une chaîne par ligne du dessin, 16 lignes et 60 colonnes au plus, affiché avec la couleur « Logo ». En JSON, `\` s'écrit `\\` et `"` s'écrit `\"` |
| `colors` | `[texte, fond, gras, souligné]` pour chaque élément de l'écran Couleurs |

Si la fenêtre est trop petite pour le logo, celui de Securetxt s'affiche à la place.
 
## Sécurité et format du coffre
 
```
coffre/
├── vault.meta        sel + paramètres Argon2id + bloc de vérification (rien de secret)
├── index.ltx         arborescence chiffrée (les noms des notes sont cachés)
├── notes/n<id>.ltx   une note chiffrée par fichier, au nom aléatoire
└── settings.json     réglages, en clair (aucune donnée personnelle)
```
 
- Dérivation de clé : Argon2id à partir du mot de passe.
- Chiffrement : XSalsa20-Poly1305 (libsodium), authentifié : toute modification d'un fichier est détectée.
- Aucune donnée en clair : le contenu et les noms des notes sont chiffrés ; seul `settings.json` ne l'est pas.
- Le format est inchangé depuis la v0.1 : chaque nouvelle version ouvre les coffres des précédentes.
 
## Dépannage
 
| Problème | Solution |
|---|---|
| Ctrl+Maj+↑ / ↓ ne fonctionne pas sous **Windows Terminal** | Windows Terminal garde ces touches pour le défilement : retire ces deux raccourcis dans ses paramètres, ou choisis d'autres touches dans Ctrl+P |
| Maj+Entrée ne fonctionne pas sous **Linux** | seuls certains terminaux la distinguent d'Entrée (kitty, foot, WezTerm, Ghostty…) : change la touche dans Ctrl+P |
| Le presse-papiers n'est pas partagé sous **Linux** | installe `xclip`, `xsel` ou `wl-clipboard` |
| « PHP introuvable » | vérifie le chemin `PHP85=` dans `securetxt.bat`, ou lance `PHP85=/chemin/vers/php ./securetxt.sh` |
| Extension manquante | active `sodium`, `mbstring` (et `ffi` sous Windows) dans `php.ini` |
 
## Mise à jour
 
1. Télécharge et décompresse la nouvelle version.
2. Copie le dossier `coffre/` de l'ancienne version dans la nouvelle.
Le format du coffre n'a pas changé : tes notes, réglages et thèmes sont conservés.
 
## Journal des versions
 
Les nouveautés de chaque version sont détaillées dans [CHANGELOG.md](CHANGELOG.md).
