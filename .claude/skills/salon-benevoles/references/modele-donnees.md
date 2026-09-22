# Modèle de données — cible du MVP

Nommage **anglais** pour tables et colonnes. Toutes les tables ont `id`, `created_at`,
`updated_at` sauf mention contraire.

## Vue d'ensemble

```
editions ─┬─< invitation_codes ──(1:1)── users
          ├─< missions ─┬─< shifts ──< assignments >── users
          └─< time_slots ─────┘
```

## `editions`

Une seule ligne en MVP (« Salon de la Danse 2027 »), seedée. Le CRUD multi-éditions est hors
MVP, **mais la clé étrangère `edition_id` est posée dès maintenant** sur les tables liées :
l'ajouter après coup coûterait une reprise de toutes les requêtes.

| Colonne | Type | Rôle |
|---|---|---|
| `name` | string | « Salon de la Danse 2027 » |
| `starts_on` / `ends_on` | date | 2027-05-14 / 2027-05-16 |
| `registration_opens_at` | datetime nullable | Ouverture de la composition du planning |
| `registration_closes_at` | datetime nullable | Fermeture |
| `is_locked` | bool, défaut `false` | Verrouillage manuel global, prioritaire sur les dates |
| `min_slots_per_volunteer` | tinyint, défaut `1` | Quota minimum |
| `max_slots_per_volunteer` | tinyint, défaut `3` | Quota maximum |
| `is_active` | bool, défaut `true` | Édition courante |

Méthode utile sur le modèle : `registrationIsOpen()` — vrai si `!is_locked` **et** que
l'instant présent est dans la fenêtre (une borne nulle = pas de limite de ce côté).

## `users`

Breeze livre une colonne `name` unique. **La migration du lot 0 la remplace** par
`first_name` / `last_name`, conformément aux champs requis du cahier des charges.

| Colonne | Type | Rôle |
|---|---|---|
| `first_name` | string | Prénom — requis |
| `last_name` | string | Nom — requis |
| `email` | string unique | Requis |
| `phone` | string | Téléphone — requis |
| `password` | string | Mot de passe sécurisé |
| `photo_path` | string nullable | Photo récente, **obligatoire à l'inscription**, stockée sur le disque `public` |
| `role` | string | Enum `UserRole` : `volunteer` \| `admin` |
| `profile_locked_at` | datetime nullable | Renseigné dès la création : profil non modifiable par le bénévole |
| `planning_validated_at` | datetime nullable | Null = brouillon, rempli = planning verrouillé |
| `edition_id` | FK nullable | Édition de rattachement |

Accesseur `full_name`. Scopes `volunteers()` et `admins()`.

## `invitation_codes`

| Colonne | Type | Rôle |
|---|---|---|
| `code` | string **unique**, indexé | Code envoyé par e-mail après sélection |
| `edition_id` | FK | |
| `used_at` | datetime nullable | Null = disponible |
| `user_id` | FK nullable | Compte créé avec ce code |

Un code est **consommé une seule fois** : c'est ce qui garantit l'unicité « une personne =
un compte ». La consommation du code et la création du compte se font dans **une même
transaction**, avec verrou sur la ligne du code pour éviter la double utilisation
concurrente.

## `time_slots`

Les 5 tranches horaires, seedées. Table à part plutôt que colonnes en dur : la grille du
planning s'affiche en lignes de tranches, et les règles d'enchaînement ont besoin d'un ordre.

| Colonne | Type | Rôle |
|---|---|---|
| `edition_id` | FK | |
| `starts_at` / `ends_at` | time | `08:30` / `10:00` |
| `position` | tinyint | 1 à 5 — **sert au calcul des 3 consécutifs** |

## `missions`

| Colonne | Type | Rôle |
|---|---|---|
| `edition_id` | FK | |
| `name` | string | « Accueil exposants » |
| `slug` | string | |
| `is_public` | bool, défaut `true` | `false` pour Billetterie et Caisse |
| `instructions` | text nullable | Consignes affichées sur la fiche récap |
| `position` | tinyint | Ordre d'affichage |

`is_public = false` ⇒ invisible dans toute vue bénévole, attribuable seulement par l'admin.

## `shifts`

Un créneau réservable = **une mission × un jour × une tranche horaire**.
9 missions publiques × 3 jours × 5 tranches = 135 lignes, plus 30 restreintes.

| Colonne | Type | Rôle |
|---|---|---|
| `edition_id` | FK | |
| `mission_id` | FK | |
| `time_slot_id` | FK | |
| `date` | date | 2027-05-14, 15 ou 16 |
| `capacity` | smallint | Jauge maximale, **paramétrable par créneau** |

Contrainte d'unicité sur `(mission_id, time_slot_id, date)`.

Exposer `remaining_places` (capacité moins réservations) et un statut de jauge
`green` / `orange` / `red`. **Ne jamais exposer la liste des bénévoles d'un shift à un
bénévole** — voir la règle de confidentialité.

## `assignments`

| Colonne | Type | Rôle |
|---|---|---|
| `user_id` | FK | |
| `shift_id` | FK | |
| `assigned_by_admin` | bool, défaut `false` | Attribution forcée par un admin |

Contrainte d'**unicité sur `(user_id, shift_id)`**.

Il n'y a pas de colonne de statut sur `assignments` : l'état brouillon / validé appartient au
bénévole entier (`users.planning_validated_at`), pas à chaque ligne. Une réservation existe
dès le clic ; la validation ne fait que verrouiller l'ensemble.

## Comptage des places : la seule source de vérité

Le nombre de places restantes se calcule **par requête** sur `assignments`, jamais via un
compteur dénormalisé sur `shifts` — un compteur se désynchronise.

La vérification de capacité et l'insertion doivent être **atomiques** : transaction +
verrou sur la ligne `shifts`. Sans cela, deux bénévoles cliquant en même temps sur le
dernier créneau passent tous les deux. C'est le seul endroit du MVP où la concurrence est
réellement un risque — 130 bénévoles se connectant à l'ouverture des inscriptions.

## Service de règles métier

Toutes les règles vivent dans **un seul service** (`app/Services/`), utilisé par le
FormRequest bénévole comme par l'action admin :

- `canBook(User, Shift): Result` — vérifie quota max, chevauchement, 3 consécutifs, capacité,
  fenêtre d'inscription, planning non verrouillé. Retourne un résultat porteur d'un message
  explicite en français.
- `canValidate(User): Result` — vérifie le quota minimum.

L'admin appelle le même service mais **court-circuite les règles** en passant par une
attribution forcée, tracée par `assigned_by_admin`.

## Seeders

| Seeder | Contenu |
|---|---|
| `EditionSeeder` | L'édition 2027 avec ses quotas |
| `TimeSlotSeeder` | Les 5 tranches |
| `MissionSeeder` | Les 9 publiques + 2 restreintes |
| `ShiftSeeder` | Le produit cartésien missions × jours × tranches, capacité par défaut |
| `AdminSeeder` | Un compte admin de développement |
| `InvitationCodeSeeder` | Un lot de codes de test |

Les identifiants du compte admin de développement viennent de `.env`, **jamais en dur dans
le seeder**.
