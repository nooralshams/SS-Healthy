#!/bin/bash
# Régénère les 10 diagrammes PNG (fond blanc, résolution x2) à partir des sources PHP.
# Prérequis : PHP en ligne de commande et Microsoft Edge (Windows). Usage : bash docs/diagrammes/generer.sh

D="$(cd "$(dirname "$0")" && pwd)"; T="$(mktemp -d)"
EDGE="/c/Program Files (x86)/Microsoft/Edge/Application/msedge.exe"
declare -A NOMS=(
  [d01_cas_utilisation]=01_Cas_d_utilisation [d02_deploiement]=02_Deploiement [d03_classes]=03_Diagramme_de_classes
  [d04_sequence_inscription]=04_Sequence_inscription [d05_sequence_commande]=05_Sequence_commande [d06_mcd]=06_MCD
  [d07_mld]=07_MLD [d08_activite_commande]=08_Activite_commande [d09_etats_commande]=09_Etats_commande
  [d10_etats_abonnement]=10_Etats_abonnement)
for src in "${!NOMS[@]}"; do
  php "$D/source/rendre.php" "$(cygpath -w "$T")" "$src" > /dev/null
  IFS=, read w h < "$T/$src.taille"
  "$EDGE" --headless=new --disable-gpu --hide-scrollbars --force-device-scale-factor=2 --user-data-dir="$(cygpath -w "$T/edge")" \
    --window-size=$w,$h --virtual-time-budget=2000 --screenshot="$(cygpath -w "$D/png/${NOMS[$src]}.png")" \
    "file:///$(cygpath -m "$T/$src.html")" 2>/dev/null | grep -q "bytes written" && echo "${NOMS[$src]}.png"
done
rm -rf "$T"
