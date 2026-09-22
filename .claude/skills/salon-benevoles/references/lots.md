# Découpage en lots — MVP

8 lots. Chacun est dimensionné pour tenir dans **une session de travail** et se termine sur
un état livrable, testé et committé. Un lot ne commence pas tant que le précédent n'est pas
au vert.

L'ordre est contraint : chaque lot s'appuie sur les précédents. Les seules paires
parallélisables sont 6 et 7, si deux personnes travaillent en même temps.

---

## Lot 0 — Fondations données et thème graphique

**Objectif** : tout le schéma du MVP en place, et les tokens de la charte disponibles avant
le premier écran.

*Données* :

- Migrations de toutes les tables de `modele-donnees.md`, y compris le remplacement de
  `users.name` par `first_name` / `last_name`.
- Modèles Eloquent, relations, casts, enum `UserRole`.
- Seeders de référence : édition, tranches, missions, shifts, admin, codes d'invitation.
- Factories pour les tests.

*Thème* — voir `design-system.md` :

- Tokens `primary`, `gauge`, `danger` dans `tailwind.config.js` (Tailwind **3.4**, donc
  `theme.extend`, pas la directive `@theme` de Tailwind 4). Les neutres utilisent l'échelle
  `zinc` native.
- Police Inter chargée, Figtree retirée, chiffres tabulaires activés sur les grilles.
- Classes `dark:` retirées des vues Breeze — pas de mode sombre au MVP.
- Composants Blade de base repris : boutons primaire / secondaire / discret, champs,
  rayons 6 px et 8 px, bordures plutôt qu'ombres.

**Fin de lot** : `php artisan migrate:fresh --seed` passe, 135 shifts publics en base, les
écrans Breeze existants sont au design system, et les tests Breeze adaptés au découpage
prénom/nom repassent au vert.

---

## Lot 1 — Inscription par code d'invitation

**Objectif** : impossible de créer un compte sans code valide.

- Écran de saisie du code en amont du formulaire d'inscription.
- Formulaire adapté : prénom, nom, e-mail, téléphone, mot de passe, **photo obligatoire**.
- Upload et stockage de la photo sur le disque `public`, avec validation de type et de poids.
- Consommation du code et création du compte dans une même transaction verrouillée.
- Profil verrouillé dès la création : le bénévole ne peut plus modifier nom, e-mail, photo.
  La page profil de Breeze est réduite au changement de mot de passe.

**Fin de lot** : tests couvrant le code absent, invalide, déjà consommé, le cas nominal, et
le refus de modification du profil verrouillé.

---

## Lot 2 — Dashboard bénévole et fenêtre d'inscription

**Objectif** : l'accueil après connexion et le contrôle temporel.

- Dashboard : règles d'engagement, dates, quotas, coordonnées de l'équipe, état du planning.
- `Edition::registrationIsOpen()` et middleware ou gate associé.
- Hors fenêtre ou verrouillage manuel : planning en **consultation seule**, message clair.

**Fin de lot** : tests sur les trois états — ouvert, fermé par date, fermé manuellement.

---

## Lot 3 — Grille de planning en lecture seule

**Objectif** : afficher la grille, sans aucune réservation possible.

- Vue **mobile first** : navigation par jour, puis tranches, puis missions.
- Jauges : places restantes en toutes lettres **et** code couleur `gauge-free` /
  `gauge-tight` / `gauge-full` — vert, ambre, gris. Pas de rouge, il est réservé aux erreurs
  et aux actions destructrices : voir `design-system.md`.
- Carte de créneau conforme au composant décrit dans `design-system.md`, motif de blocage
  affiché en clair quand une règle empêche la réservation.
- Missions `is_public = false` exclues de la requête, pas seulement masquées en Blade.
- Confidentialité : aucun nom d'autre bénévole, ni en HTML ni en JSON.

**Fin de lot** : la grille est lisible sur un écran de 375 px sans défilement horizontal, un
test vérifie qu'aucun nom de bénévole tiers ne fuite dans la réponse, et l'état d'un créneau
reste compréhensible en niveaux de gris.

---

## Lot 4 — Réservation et règles métier

**Objectif** : le cœur du sujet. C'est le lot le plus dense, ne pas le charger davantage.

- Service de règles unique : quota max, non-chevauchement, pas 3 consécutifs, capacité,
  fenêtre ouverte, planning non verrouillé.
- Ajout et retrait d'un créneau en brouillon, avec message d'erreur explicite par règle.
- Transaction et verrou sur `shifts` au moment de l'insertion.
- Retour visuel immédiat via Alpine, l'état serveur restant la référence.

**Fin de lot** : un test Pest **par règle métier**, plus un test de concurrence sur le
dernier créneau disponible. Aucune règle n'est vérifiable uniquement côté client.

---

## Lot 5 — Validation définitive et fiche récapitulative

**Objectif** : figer le planning et le restituer.

- Bouton « Valider définitivement » avec pop-up de confirmation.
- Vérification du quota minimum au moment de la validation.
- `planning_validated_at` renseigné, planning en lecture seule ensuite.
- Fiche récapitulative : missions, horaires, consignes, mise en page imprimable via CSS
  `@media print`. **Pas de génération PDF** — hors MVP.

**Fin de lot** : tests sur validation sous le quota minimum, validation nominale, et refus
de toute modification après verrouillage.

---

## Lot 6 — Back-office admin : supervision

**Objectif** : voir l'état du dispositif.

- Gate ou policy `admin`, layout d'administration distinct.
- Dashboard : total bénévoles, comptes créés, plannings validés vs en attente, taux de
  remplissage par jour et par mission.
- Recherche multi-critères : nom, prénom, mission, statut de validation, jour.
- Fiche bénévole avec son planning complet.

**Fin de lot** : tests d'accès (un bénévole reçoit un 403) et justesse des compteurs.

---

## Lot 7 — Back-office admin : outrepassement et exports

**Objectif** : la main de l'administrateur, et la sortie des données.

- Modifier un planning verrouillé, déverrouiller un bénévole.
- Forcer l'attribution d'une mission restreinte (Billetterie, Caisse), avec
  `assigned_by_admin = true`.
- Réinitialiser les identifiants, modifier les informations personnelles verrouillées.
- Exports **Excel et CSV**, global ou filtré : planning général, liste par mission, fiches
  contact.

**Fin de lot** : tests sur le contournement des règles par l'admin et sur le contenu des
exports. Démo bénévole et démo admin exécutables de bout en bout.

---

## Ce qui reste hors MVP

Badges PDF avec QR Code · notifications e-mail · log d'audit horodaté · CRUD multi-éditions
et archivage. Ne pas les entamer avant que le lot 7 soit livré.
