#!/bin/sh
# ==== Securetxt : raccourci pour GNOME (et les autres bureaux Linux) ====
#
#   ./creer-raccourci.sh              crée le raccourci
#   ./creer-raccourci.sh --supprimer  le retire
#
# Le raccourci apparaît dans la liste des applications (touche Super, puis « Securetxt »),
# et sur le Bureau si tu en as un (GNOME : extension « Desktop Icons NG »).
# Chemin de PHP : PHP85=/chemin/vers/php ./creer-raccourci.sh   (sinon « php »)

set -e
DIR="$(cd "$(dirname "$0")" && pwd)"
APPS="${XDG_DATA_HOME:-$HOME/.local/share}/applications"
FILE="$APPS/securetxt.desktop"
DESK="$(xdg-user-dir DESKTOP 2>/dev/null || true)"
[ -n "$DESK" ] && [ "$DESK" != "$HOME" ] || DESK="$HOME/Desktop"
[ -d "$DESK" ] || { [ -d "$HOME/Bureau" ] && DESK="$HOME/Bureau"; }

if [ "$1" = "--supprimer" ]; then
    rm -f "$FILE" "$DESK/securetxt.desktop"
    command -v update-desktop-database >/dev/null 2>&1 && update-desktop-database -q "$APPS" 2>/dev/null || true
    echo "Raccourci Securetxt retiré."
    exit 0
fi

# ---- vérifications
PHP="${PHP85:-php}"
if ! command -v "$PHP" >/dev/null 2>&1; then
    echo "PHP introuvable ($PHP). Installe-le : sudo pacman -S php php-sodium" >&2
    exit 1
fi
if ! "$PHP" -m 2>/dev/null | grep -qi '^sodium$'; then
    echo "Attention : l'extension sodium n'est pas activée dans PHP."
    echo "  sudo pacman -S php-sodium"
    echo "  sudo sed -i 's/^;extension=sodium/extension=sodium/' /etc/php/php.ini"
fi
chmod +x "$DIR/securetxt.sh"

# ---- lanceur : un petit script qui retient le chemin de PHP choisi
LAUNCH="$DIR/.securetxt-lanceur.sh"
cat > "$LAUNCH" <<EOF
#!/bin/sh
# créé par creer-raccourci.sh : lance Securetxt avec le bon PHP
PHP85="$PHP" exec sh "$DIR/securetxt.sh" "\$@"
EOF
chmod +x "$LAUNCH"

# ---- terminal dans lequel ouvrir Securetxt : Ghostty s'il est installé, sinon le premier trouvé
TERMINAL=false
if command -v ghostty >/dev/null 2>&1; then                # Ghostty en priorité (gère aussi Maj+Entrée)
    EXEC="ghostty --title=Securetxt -e \"$LAUNCH\""
elif command -v kgx >/dev/null 2>&1; then                  # Console (GNOME 42+)
    EXEC="kgx --title=Securetxt -- \"$LAUNCH\""
elif command -v gnome-terminal >/dev/null 2>&1; then
    EXEC="gnome-terminal --title=Securetxt -- \"$LAUNCH\""
elif command -v ptyxis >/dev/null 2>&1; then               # Ptyxis (GNOME 47+)
    EXEC="ptyxis --new-window -- \"$LAUNCH\""
elif command -v kitty >/dev/null 2>&1; then
    EXEC="kitty --title Securetxt \"$LAUNCH\""
elif command -v foot >/dev/null 2>&1; then
    EXEC="foot --title=Securetxt \"$LAUNCH\""
elif command -v wezterm >/dev/null 2>&1; then
    EXEC="wezterm start -- \"$LAUNCH\""
elif command -v alacritty >/dev/null 2>&1; then
    EXEC="alacritty --title Securetxt -e \"$LAUNCH\""
elif command -v konsole >/dev/null 2>&1; then
    EXEC="konsole -e \"$LAUNCH\""
elif command -v xterm >/dev/null 2>&1; then
    EXEC="xterm -T Securetxt -e \"$LAUNCH\""
else                                                        # on laisse le bureau choisir
    EXEC="\"$LAUNCH\""
    TERMINAL=true
fi

ICON="$DIR/securetxt.png"
[ -f "$ICON" ] || ICON="utilities-terminal"

# ---- fichier .desktop
mkdir -p "$APPS"
cat > "$FILE" <<EOF
[Desktop Entry]
Type=Application
Version=1.0
Name=Securetxt
GenericName=Notes chiffrées
Comment=Notes chiffrées en mode texte
Exec=$EXEC
Path=$DIR
Icon=$ICON
Terminal=$TERMINAL
Categories=Utility;TextEditor;
Keywords=notes;chiffrement;texte;coffre;
StartupNotify=false
EOF
chmod +x "$FILE"
command -v update-desktop-database >/dev/null 2>&1 && update-desktop-database -q "$APPS" 2>/dev/null || true
echo "Raccourci créé : $FILE"
echo "  → touche Super, puis tape « Securetxt »."

# ---- icône sur le Bureau, si le dossier existe
if [ -d "$DESK" ]; then
    cp "$FILE" "$DESK/securetxt.desktop"
    chmod +x "$DESK/securetxt.desktop"
    # GNOME : marque le lanceur comme fiable (sinon « Autoriser le lancement » à faire à la main)
    command -v gio >/dev/null 2>&1 && gio set "$DESK/securetxt.desktop" metadata::trusted true 2>/dev/null || true
    echo "Icône posée sur le Bureau : $DESK/securetxt.desktop"
    echo "  (GNOME n'affiche le Bureau qu'avec l'extension « Desktop Icons NG ». Si l'icône demande"
    echo "   une confirmation : clic droit > « Autoriser le lancement ».)"
fi
