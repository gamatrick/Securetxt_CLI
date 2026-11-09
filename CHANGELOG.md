# Journal des versions

Les nouveautés de chaque version.

## [0.4.0] — 2026-11-04

- Export chiffré : e / E, puis C, crée un seul fichier `.stx` protégé par un mot de passe choisi pour l'export (Argon2id + XChaCha20-Poly1305).
- Import d'un export chiffré : I sur un `.stx` demande son mot de passe, vérifie tout en mémoire, puis range les notes dans le coffre avec le chiffrement du coffre. Rien n'est importé si le mot de passe est faux ou le fichier abîmé.
- Un dossier importé qui existe déjà devient « Nom (2) » ; les liens `[[chemin/complet]]` vers les notes importées suivent.
- Saisie masquée (`*`) pour les mots de passe d'export, et choix du format à l'export (C = chiffré, T = texte en clair).
- Éditeur : le curseur ne recouvre plus la dernière lettre quand une ligne atteint le bord de la fenêtre.
- Le format du coffre ne change pas : compatible avec la v0.3.9.

## [0.3.9] — 2026-11-04

- `.gitignore` : la dernière ligne `desktop.ini.idea/` (deux règles collées) est séparée en `desktop.ini` et `.idea/`, donc `desktop.ini` est de nouveau ignoré.
- `.gitignore` : suppression des règles `.idea/…` en double, puisque tout le dossier `.idea/` est déjà ignoré.

## [0.3.8] — 2026-10-21

- `creer-raccourci.sh` : raccourci dans la liste des applications et sur le Bureau (GNOME, KDE…), ouverture dans Ghostty s'il est installé, sinon dans le premier terminal trouvé. `--supprimer` le retire.
- Paramètres > Créer un raccourci utilise ce script sous Linux.
- Le numéro de version est lu dans le fichier `VERSION`.

## [0.3.7] — 2026-10-21

- La recherche (Ctrl+F) trouve aussi les dossiers par leur nom ; ils sont listés en premier et Entrée ouvre le dossier dans l'arborescence.
- Le nom d'une note est cherché sans son dossier.

## [0.3.6] — 2026-10-14

- Thèmes personnalisés dans `settings.json` avec un logo optionnel affiché à la connexion.

## [0.3.5] — 2026-10-08

- README réorganisé : notes parentes et enfants, raccourcis, sélection, souris, arborescence.

## [0.3.4] — 2026-10-08

- Couleurs réglables séparément pour le symbole ► / ▼ et pour les notes enfants.

## [0.3.3] — 2026-10-08

- Symboles ► (enfants cachés) / ▼ (affichés) ; Espace, → et ← affichent ou cachent les enfants.

## [0.3.2] — 2026-10-08

- L'arborescence montre les notes parentes et enfants (liens `[[…]]`).
