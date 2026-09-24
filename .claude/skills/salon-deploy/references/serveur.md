# VPS de test — inventaire, opérations, pièges

Toutes les commandes distantes : `ssh -i ~/.ssh/salon_deploy root@vps123929.serveur-vps.net '…'`.
Artisan se lance toujours en `www-data`, avec un HOME inscriptible (sinon Tinker échoue
sur `/var/www/.config/psysh`) :

```bash
cd /var/www/salon-danse && sudo -u www-data XDG_CONFIG_HOME=/tmp HOME=/tmp php artisan …
```

## Inventaire

| Élément | Valeur |
|---|---|
| Système | Debian 12 (bookworm), 40 Go disque |
| Web | nginx 1.22 — vhost `/etc/nginx/sites-available/salon-danse` (lien dans `sites-enabled`, `default` supprimé) |
| PHP | 8.3 via le dépôt **Sury** (Debian 12 ne fournit que 8.2 ; le projet exige `^8.3`), FPM sur `/run/php/php8.3-fpm.sock` |
| Limites PHP | `/etc/php/8.3/fpm/conf.d/99-salon.ini` : `upload_max_filesize=8M`, `post_max_size=12M` (photo bénévole jusqu'à 4 Mo) ; nginx `client_max_body_size 12M` |
| Extensions | sqlite3, pdo_sqlite, gd, mbstring, intl, zip, curl, xml, bcmath |
| Outils | Composer 2 (`/usr/local/bin/composer`), sqlite3, rsync, git, unzip — **pas de Node** : les assets sont compilés en local |
| HTTPS | Let's Encrypt pour `vps123929.serveur-vps.net`, redirection 80 → 443, renouvellement par `certbot.timer` |
| `.env` | `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://vps123929.serveur-vps.net`, `LOG_LEVEL=warning`, `MAIL_MAILER=log` (aucun e-mail ne part), `SALON_ADMIN_PASSWORD` **vide** volontairement |

Logs : `storage/logs/laravel.log`, `/var/log/nginx/salon-danse.{access,error}.log`.
Des bots scannent déjà le serveur (`/.env`, `/.git/config`, `/info.php`) : ces requêtes
bloquées (403/404) dans le log nginx sont normales.

## Opérations courantes

### Créer un compte admin

Pas via `AdminSeeder` (il ne gère qu'un compte, lu dans `.env`) : directement en base,
mot de passe généré sur place et affiché **une seule fois** à l'utilisateur.

```php
$password = Illuminate\Support\Str::password(20, symbols: false);
$user = App\Models\User::firstOrNew(['email' => 'nouvel.admin@exemple.fr']);
$user->forceFill([
    'first_name' => 'Prénom', 'last_name' => 'Nom', 'phone' => '',
    'password' => Illuminate\Support\Facades\Hash::make($password),
    'role' => App\Enums\UserRole::Admin,
    'email_verified_at' => $user->email_verified_at ?? now(),
    'profile_locked_at' => $user->profile_locked_at ?? now(),
    'edition_id' => App\Models\Edition::current()?->id,
])->save();
echo $password;
```

Si l'utilisateur impose son propre mot de passe, le passer tel quel — mais ne jamais
l'écrire dans un fichier.

### Supprimer un compte

`App\Models\User::where('email', '…')->delete();` — les affectations suivent
(`cascadeOnDelete`), le code d'invitation consommé reste avec `user_id` à `null`.

### Régénérer les données de démo

`VolunteerSeeder` refuse de tourner quand `APP_ENV=production`, et Faker est une
dépendance de dev absente du serveur. Séquence (plusieurs minutes, lancer en `nohup`) :

1. `composer install` (avec dev) — `COMPOSER_ALLOW_SUPERUSER=1`
2. `.env` : `APP_ENV=local`, `SALON_SEED_VOLUNTEERS=100`, puis `config:clear`
3. `php artisan db:seed --class=VolunteerSeeder --force`
4. `.env` : `APP_ENV=production`, puis `composer install --no-dev --optimize-autoloader`
5. `config:cache`, `route:cache`, `view:cache`, `chown -R www-data:www-data storage bootstrap/cache database`

**Vérifier ensuite** : `APP_ENV=production` et `vendor/fakerphp` absent. Le seeder est
idempotent (il complète jusqu'à `SALON_SEED_VOLUNTEERS`). Les bénévoles fictifs n'ont pas
de photo (`photo_path` à `null`). Il peut consommer un code d'invitation libre existant.

### Effacer les données de démo

```php
App\Models\User::where('email', 'like', '%@benevoles.test')->delete();
App\Models\InvitationCode::whereNotNull('used_at')->whereNull('user_id')->delete();
```

### Codes d'invitation

Aucun écran d'admin ne les liste : `App\Models\InvitationCode::whereNull('used_at')->pluck('code')`.
Le suivi d'usage passe par `used_at` / `user_id` (il n'existe pas de colonne `used_by`).

### Restaurer une sauvegarde

```bash
sudo -u www-data php /var/www/salon-danse/artisan down
cp /var/backups/salon-danse/database-AAAAMMJJ-HHMMSS-<commit>.sqlite /var/www/salon-danse/database/database.sqlite
chown www-data:www-data /var/www/salon-danse/database/database.sqlite
sudo -u www-data php /var/www/salon-danse/artisan up
```

### Basculer sur le domaine de l'école (quand le DNS existera)

1. Vérifier : `nslookup adrien-berthe.angers.mds-project.fr 8.8.8.8` → `180.149.198.113`
2. `certbot --nginx --expand -d vps123929.serveur-vps.net -d adrien-berthe.angers.mds-project.fr --non-interactive --redirect`
3. `.env` : `APP_URL=https://adrien-berthe.angers.mds-project.fr`, puis `config:cache`
4. Mettre à jour `PUBLIC_URL` par défaut dans `scripts/deploy.sh` et le tableau du `SKILL.md`

## Provisioning d'un serveur vierge

Si le VPS est réinstallé (seul SSH tourne), dans l'ordre :

1. Paquets : `ca-certificates curl gnupg lsb-release unzip git sqlite3 rsync nginx`
2. Dépôt Sury : clé `https://packages.sury.org/php/apt.gpg` dans `/usr/share/keyrings/deb.sury.org-php.gpg`,
   source `deb [signed-by=…] https://packages.sury.org/php/ bookworm main`
3. `php8.3-fpm php8.3-cli php8.3-sqlite3 php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl`
4. Composer via l'installeur officiel dans `/usr/local/bin/composer`
5. Code dans `/var/www/salon-danse` (premier envoi à la main : `deploy.sh` exige un `.env` existant)
6. `.env` de production écrit sur le serveur (modèle : `.env.example`, valeurs de l'inventaire ci-dessus), `chmod 640`, puis `key:generate --force`
7. `touch database/database.sqlite`, `migrate --force`, puis les seeders **un par un** :
   `EditionSeeder TimeSlotSeeder MissionSeeder ShiftSeeder InvitationCodeSeeder` ; admin comme ci-dessus
8. `storage:link`, permissions `www-data`, `99-salon.ini`, vhost nginx (`root …/public`, `try_files $uri $uri/ /index.php?$query_string`, `location ~ /\.(?!well-known).* { deny all; }`)
9. `certbot --nginx -d vps123929.serveur-vps.net --agree-tos -m <e-mail utilisateur> --redirect`

## Pièges rencontrés

| Symptôme | Cause | Parade |
|---|---|---|
| `tar: Cannot connect to C: resolve failed` | tar lit `C:/…` comme un hôte distant | chemins msys (`/c/…`, `/tmp/…`) ou `--force-local` |
| `le propriétaire ne peut pas être changé en uid 197609` | uid Windows dans l'archive | `tar -x … --no-same-owner` |
| `client_loop: send disconnect: Connection reset` | commande longue, session coupée | `nohup` ; l'opération a souvent abouti quand même : vérifier l'état avant de relancer |
| Tinker : `Writing to directory /var/www/.config/psysh is not allowed` | HOME de www-data non inscriptible | `XDG_CONFIG_HOME=/tmp HOME=/tmp` |
| `Do not run Composer as root` | avertissement Composer | `COMPOSER_ALLOW_SUPERUSER=1` |
| `grep: -P supports only unibyte and UTF-8 locales` | Git Bash | `grep -oE` ou `sed -E` |
| 419 au login scripté | jeton CSRF non extrait | récupérer `_token` avec les cookies de la même session |
| `.env` modifié sans effet | config en cache | `config:clear` avant, `config:cache` après |
| Mot de passe passé en ligne de commande refusé | classifieur de sécurité de Claude Code | passer par la clé SSH, ne pas contourner |
