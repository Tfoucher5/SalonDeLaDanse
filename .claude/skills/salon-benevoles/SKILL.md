---
name: salon-benevoles
description: Contexte complet du projet Salon de la Danse d'Angers — plateforme web de gestion des 130 bénévoles (Laravel 13 + Breeze Blade + SQLite). À charger dès qu'une tâche touche ce projet : code d'invitation, inscription bénévole, upload photo, planning, créneaux, missions, jauges de capacité, règles métier, verrouillage, back-office admin, exports Excel/CSV. Remplace la lecture du cahier des charges PDF.
---

# Salon de la Danse — plateforme bénévoles

## Le projet

Salon de la Danse d'Angers, porté par l'association **JayDance Fam** : 2,5 jours au Centre
de Congrès, 120 exposants, 7 000 visiteurs, **130 bénévoles** à gérer. Objectif : remplacer
les tableurs par une plateforme web sur-mesure, réutilisable d'une édition à l'autre.

Le recrutement se fait **hors plateforme** (Google Forms). Les candidats retenus reçoivent
par e-mail un **code d'invitation unique**. La plateforme prend le relais : création de
compte, composition du planning selon les disponibilités, validation.

Deux rôles : **bénévole** et **administrateur**.

## Périmètre : MVP uniquement

Le MVP tient en 5 blocs. **Tout le reste est hors périmètre tant que le MVP n'est pas
terminé** — ne l'implémente pas, ne l'anticipe pas au-delà de ce que dit
`references/modele-donnees.md`.

| # | Bloc MVP |
|---|---|
| 1 | Authentification + inscription par code d'invitation + upload photo |
| 2 | Moteur de planning interactif **mobile first**, jauges et code couleur |
| 3 | Contrôle des règles métier (1 à 3 créneaux, non-chevauchement, jauges max) |
| 4 | Verrouillage du planning + fiche récapitulative bénévole |
| 5 | Back-office admin : vue d'ensemble, modifications manuelles, export Excel/CSV |

**Hors MVP** (ne pas coder) : badges PDF avec QR Code, notifications e-mail automatiques,
log d'audit horodaté, CRUD multi-éditions et archivage.

## Stack en place

Laravel 13.32 · PHP 8.3 · Breeze v2.4 stack **Blade + Alpine + Tailwind** · Pest 4.7 ·
Vite 8 · **SQLite** (`database/database.sqlite`, ignoré par git).

Lancer : `composer run dev` · Tester : `composer run test` · Compte de test seedé :
`test@example.com` / `password`.

## Invariants — à ne jamais casser

1. **Mobile first.** Le planning est conçu pour le smartphone d'abord, l'écran large ensuite.
2. **Confidentialité stricte.** Un bénévole ne voit **jamais** le nom des autres bénévoles,
   uniquement le **nombre de places restantes** sur un créneau.
3. **Inscription fermée par défaut.** Sans code d'invitation valide et non consommé, aucun
   compte ne peut être créé.
4. **Profil verrouillé.** Après création, nom / prénom / e-mail / photo ne sont modifiables
   que par un administrateur.
5. **Règles métier côté serveur.** Toute contrainte de planning est validée en base et en
   PHP. Le front n'est qu'un confort, jamais la source de vérité.
6. **Design system respecté.** Interface neutre (échelle `zinc`), un seul accent indigo
   `#4338CA` pour les actions, couleur réservée à l'information. Aucune couleur en dur dans
   les vues : uniquement les tokens Tailwind. Détail dans `references/design-system.md`.
7. **Aucun secret dans le code.** Identifiants, clés et mots de passe vivent dans `.env`,
   jamais dans un fichier versionné — `.env.example` ne contient que des placeholders vides.

## Conventions de code

- Français pour l'UI, les libellés, les messages de validation et les commentaires.
  **Anglais** pour le code : noms de tables, colonnes, classes, méthodes, routes.
- Un `FormRequest` par action qui écrit. Jamais de validation dans le contrôleur.
- Les règles de planning vivent dans **un seul service** (`app/Services/`), appelé par le
  FormRequest comme par l'admin. Pas de duplication de règle.
- Contrôleurs fins, `Policy` pour les autorisations, `Enum` PHP natif pour les statuts.
- Chaque lot livre ses tests Pest. Un lot sans test au vert n'est pas terminé.
- Migrations : jamais modifier une migration déjà poussée, en créer une nouvelle.

## Références — à lire seulement quand c'est utile

Ne charge que le fichier dont tu as besoin, ils sont conçus pour être lus séparément :

| Fichier | Quand le lire |
|---|---|
| `references/domaine.md` | Règles métier détaillées, jours, créneaux horaires, liste des missions, quotas, code couleur |
| `references/modele-donnees.md` | Schéma de base cible du MVP : tables, colonnes, relations, contraintes |
| `references/design-system.md` | Dès qu'on touche à une vue : palette, typographie, formes, composants, contrastes |
| `references/lots.md` | Découpage en 8 lots de travail, avec périmètre et critères de fin pour chacun |

`.claude/image.png` est une capture du site officiel. **La charte graphique du Salon n'est
délibérément pas suivie** — le client l'a écartée. Ne charge pas cette image et ne cherche
pas à t'en inspirer : `references/design-system.md` fait autorité.

Le pilotage des sessions (quels prompts, quand repartir d'un contexte neuf) est décrit dans
`PILOTAGE-IA.md` à la racine du dépôt. C'est un document pour l'humain, pas pour toi.

## Points ouverts

À signaler à l'utilisateur si une tâche les touche, sans trancher seul :

- Le cahier des charges annonce « 5 créneaux de **2 heures** » mais liste `8h30-10h00`,
  soit **1h30** pour le premier. Le modèle stocke des heures de début et de fin réelles,
  ce qui rend la question sans effet technique — mais l'affichage « 2h » serait faux.
- « Logistique (Niveau 0 + -2) » : une seule mission ou deux missions distinctes ?
  Traité comme **une seule** mission pour l'instant.
- « Valider des profils mineurs » suppose une date de naissance, absente de la liste des
  champs requis. **Hors MVP**, aucune gestion des mineurs pour l'instant.
La direction artistique, elle, est **tranchée** : voir `references/design-system.md`. Ne la
remets pas en question, applique-la.
