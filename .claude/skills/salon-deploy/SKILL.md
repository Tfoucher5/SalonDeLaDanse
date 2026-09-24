---
name: salon-deploy
description: Déploiement et exploitation du site Salon de la Danse sur le VPS de test (vps123929.serveur-vps.net, Debian 12, nginx, PHP 8.3, SQLite). À charger pour toute demande de déploiement, mise en ligne, mise à jour du serveur, « pousser en prod », sauvegarde ou restauration de la base distante, données de démo sur le serveur, comptes admin distants, certificat HTTPS, bascule vers le domaine de l'école, logs ou panne du site en ligne.
---

# Déploiement — VPS de test

## L'essentiel

| | |
|---|---|
| URL publique | https://vps123929.serveur-vps.net |
| Domaine cible | `adrien-berthe.angers.mds-project.fr` — **ne résout pas encore** (DNS à créer par l'école vers `180.149.198.113`) |
| SSH | `ssh -i ~/.ssh/salon_deploy root@vps123929.serveur-vps.net` (clé seule, jamais de mot de passe) |
| Application | `/var/www/salon-danse`, propriétaire `www-data` |
| Base | SQLite `/var/www/salon-danse/database/database.sqlite` |
| Sauvegardes | `/var/backups/salon-danse/` (10 dernières, une par déploiement) |

Inventaire complet, provisioning d'un serveur vierge et pièges connus :
`references/serveur.md`. **Le lire avant toute opération autre qu'un déploiement standard.**

## Déployer

Depuis la racine du dépôt, sous Git Bash :

```bash
bash .claude/skills/salon-deploy/scripts/deploy.sh --dry-run   # fichiers modifiés, variables SALON_* manquantes, migrations en attente
bash .claude/skills/salon-deploy/scripts/deploy.sh             # déploiement réel
```

Le script déploie **le commit `HEAD`** et refuse un arbre de travail sale. Il enchaîne :
build Vite local → `git archive` + `public/build` → envoi → sauvegarde de la base →
maintenance → `rsync --delete` du code → `composer install --no-dev` → `migrate --force`
→ caches → rechargement de PHP-FPM → levée de maintenance → contrôle HTTP public.

Ce qui n'est **jamais** écrasé ni supprimé sur le serveur : `.env`, la base SQLite,
`storage/` (photos des bénévoles, logs), `public/storage`, `vendor/`.

En cas d'échec, le site reste en maintenance et le script affiche la commande de
restauration de la sauvegarde prise juste avant.

### Avant de lancer

1. Le déploiement modifie un site public : le lancer quand l'utilisateur le demande,
   pas de sa propre initiative. Montrer d'abord le `--dry-run` si des migrations sont en attente.
2. Tests verts en local (`php artisan test --compact`).
3. Nouvelle dépendance Composer ? Vérifier qu'elle est dans `require` et non `require-dev` :
   le serveur installe en `--no-dev`.
4. Nouvelle variable `.env` ? L'ajouter **sur le serveur** (`/var/www/salon-danse/.env`)
   avant de déployer, puis `config:cache`. Elle ne voyage pas avec le code.

## Règles

- **Aucun secret dans le dépôt** (règle de l'organisation). Ni mot de passe, ni clé, ni
  contenu du `.env` de production dans ce skill, un script ou un commit. Les mots de passe
  se génèrent sur le serveur et ne s'affichent qu'une fois à l'utilisateur.
- Jamais `rm -rf /var/www/salon-danse`, `migrate:fresh`, `db:wipe` ni `db:seed` sans
  `--class` sur le serveur : la base contient les comptes et les plannings.
- Jamais `DatabaseSeeder` en production : il crée sans condition `test@example.com`.
- Une commande longue (seeding, plusieurs minutes) peut couper la session SSH : la lancer
  avec `nohup … > /tmp/x.log 2>&1 &` puis lire le log.

## Comptes existants (sans mot de passe)

| E-mail | Rôle |
|---|---|
| `raytheck@yahoo.fr` | admin principal |
| `admin@admin.fr` | admin des testeurs — à supprimer avant une vraie mise en production |
| `*@benevoles.test` | 100 bénévoles fictifs (données de démo) |

Créer, supprimer un compte ou régénérer des données de démo : `references/serveur.md`.
