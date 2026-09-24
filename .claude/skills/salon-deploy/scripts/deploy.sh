#!/usr/bin/env bash
#
# Deploie le commit HEAD sur le VPS de test du Salon de la Danse.
#
# A lancer depuis la racine du depot, sous Git Bash :
#   bash .claude/skills/salon-deploy/scripts/deploy.sh            # deploiement reel
#   bash .claude/skills/salon-deploy/scripts/deploy.sh --dry-run  # simulation, rien ne change
#
# Aucun secret ici : l acces passe par la cle SSH ~/.ssh/salon_deploy,
# le .env de production vit uniquement sur le serveur.

set -euo pipefail

HOST="${SALON_DEPLOY_HOST:-vps123929.serveur-vps.net}"
SSH_USER="${SALON_DEPLOY_USER:-root}"
KEY="${SALON_DEPLOY_KEY:-$HOME/.ssh/salon_deploy}"
PUBLIC_URL="${SALON_DEPLOY_URL:-https://vps123929.serveur-vps.net}"

DRY_RUN=0
[ "${1:-}" = "--dry-run" ] && DRY_RUN=1

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SSH_OPTS=(-i "$KEY" -o BatchMode=yes -o ServerAliveInterval=30 -o ConnectTimeout=20)

step() { printf '\n### %s\n' "$*"; }
fail() { printf '\nECHEC : %s\n' "$*" >&2; exit 1; }

[ -f artisan ] || fail "lancer depuis la racine du depot (artisan introuvable)."
[ -f "$KEY" ] || fail "cle SSH absente : $KEY"

step "1/5 verifications locales"
if [ -n "$(git status --porcelain --untracked-files=no)" ]; then
  git status --short --untracked-files=no
  fail "modifications non commitees : git archive ne deploie que HEAD, commiter d abord."
fi
UNTRACKED="$(git ls-files --others --exclude-standard)"
if [ -n "$UNTRACKED" ]; then
  echo "attention, fichiers non suivis NON deployes :"
  echo "$UNTRACKED" | sed 's/^/  /'
fi
COMMIT="$(git rev-parse --short HEAD)"
echo "commit a deployer : $COMMIT ($(git log -1 --format=%s))"
ssh "${SSH_OPTS[@]}" "$SSH_USER@$HOST" true || fail "connexion SSH impossible."

step "2/5 build des assets"
npm run build >/dev/null 2>&1 || fail "npm run build a echoue (relancer a la main pour voir l erreur)."
[ -f public/build/manifest.json ] || fail "public/build/manifest.json absent apres le build."

step "3/5 paquet"
# Chemins msys (/tmp/...) : un chemin C:/... fait croire a tar qu il s agit d un hote distant.
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
git archive --format=tar HEAD -o "$WORK/release.tar"
tar --append -f "$WORK/release.tar" public/build
gzip "$WORK/release.tar"
echo "$(tar -tzf "$WORK/release.tar.gz" | wc -l) entrees, $(du -h "$WORK/release.tar.gz" | cut -f1)"

step "4/5 envoi et mise a jour distante"
scp "${SSH_OPTS[@]}" -q "$WORK/release.tar.gz" "$SSH_USER@$HOST:/tmp/salon-release.tar.gz"
# tr retire d eventuels CR : un script en CRLF casse bash cote serveur.
tr -d '\r' < "$SCRIPT_DIR/remote-update.sh" \
  | ssh "${SSH_OPTS[@]}" "$SSH_USER@$HOST" "DRY_RUN=$DRY_RUN COMMIT=$COMMIT bash -s"

step "5/5 verification publique"
if [ "$DRY_RUN" = 1 ]; then
  echo "simulation : rien n a ete modifie sur le serveur."
  exit 0
fi
for path in / /login /mentions-legales; do
  code="$(curl -s -o /dev/null -m 25 -w '%{http_code}' "$PUBLIC_URL$path")"
  echo "$code  $path"
  [ "$code" = 200 ] || fail "$PUBLIC_URL$path repond $code."
done
echo
echo "Deploiement de $COMMIT termine : $PUBLIC_URL"
