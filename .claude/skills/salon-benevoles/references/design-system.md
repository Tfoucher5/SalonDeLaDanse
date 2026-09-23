# Design system — « Salon Danse Élan »

**Source de vérité visuelle : la maquette Stitch « Modern UI Redesign Concept »**
(projet `10522777241175286008`, écrans Tableau de bord, Planning des créneaux, Mon
Profil). Adoptée le 23 septembre 2026, elle remplace la charte neutre zinc/indigo.
La charte du site officiel (`.claude/image.png`) reste écartée.

## L'intention

Faire d'un tableur opérationnel un compagnon chaleureux et rythmé, inspiré de la
scène : fond porcelaine, surfaces blanches arrondies qui flottent sur une ombre chaude,
action en terracotta, états en prune, disponibilité en émeraude.

Ce qui ne change pas : **la couleur d'information reste distincte de la couleur
d'action.** Les jauges (émeraude / ambre / gris) ne réutilisent jamais le terracotta.
« Complet » est gris, jamais rouge. Toute couleur est doublée d'un mot.

**Côté back-office, l'échelle s'inverse** (`AppnumsStaffingLevel`) : le but de
l'équipe est de pourvoir chaque poste. Tout indicateur de remplissage va du rouge
(`danger`, moins de la moitié pourvue) à l'ambre (`gauge-tight`, en cours) puis au vert
(`gauge-free`, complet ou objectif atteint). Les jauges de places partent pleines et
se vident à chaque inscription, comme côté bénévole.

## Palette (tokens `tailwind.config.js`)

| Token | Hex | Usage |
|---|---|---|
| `zinc-50` | `#FAFAF7` | Fond porcelaine de la page |
| `zinc-100` | `#F4F0EC` | Tuiles, champs en lecture seule, onglets inactifs |
| `zinc-200` | `#EAE3DE` | Séparateurs, pistes de jauge |
| `zinc-400` | `#A59CA1` | Icônes secondaires, désactivé |
| `zinc-500` | `#716B70` | Texte secondaire, libellés |
| `zinc-900` | `#1F1A1C` | Texte courant et titres |
| `primary` | `#B93A24` | Boutons primaires, liens, onglet actif (blanc dessus : 5,7:1) |
| `primary-hover` | `#9A2C19` | Survol |
| `primary-bright` | `#E0533C` | Terracotta de marque : logo, anneau de focus, puces décoratives. **Jamais en fond de texte blanc** (3,6:1) |
| `primary-soft` | `#FDEBE7` | Fond de badge, onglet actif, halos |
| `plum` / `plum-soft` | `#6C2E58` / `#F6E8F1` | États du planning (Brouillon), étiquettes de section |
| `gauge-free` | `#0F766E` | Places disponibles |
| `gauge-tight` | `#B45309` | Presque complet |
| `gauge-full` | `#716B70` | Complet ou indisponible |
| `danger` | `#B91C1C` | Échec de validation, action destructrice |

L'échelle garde le nom `zinc` pour que toutes les vues en héritent, mais ses valeurs
sont réchauffées. Aucune autre palette Tailwind, aucun hexadécimal dans les vues.

## Typographie

**Plus Jakarta Sans**, une seule famille (400 à 800). Titres en 700-800 avec
`tracking-tight` ; sur-titres en capitales 11 px, graisse 700, `tracking-[0.08em]`.
Chiffres tabulaires (`tabular-grid`) sur la grille et les tableaux.

## Formes et profondeur

| Élément | Traitement |
|---|---|
| Cartes, panneaux | `rounded-2xl`, fond blanc, `ring-1 ring-zinc-900/5`, `shadow-card` |
| Bandeaux héros | `rounded-3xl` |
| Boutons, champs, tuiles | `rounded-xl` |
| Badges, pastilles, avatars, onglets de nav | `rounded-full` (capsule) — **jamais** un `<button>` ou un champ |

Ombres nommées uniquement, teintées prune : `shadow-card` (surfaces), `shadow-lift`
(survol d'une carte de créneau), `shadow-cta` (bouton primaire), `shadow-overlay`
(menu déroulant et modale seulement).

## Composants clés

- **Bouton** : primaire terracotta plein avec `shadow-cta`, `active:scale-[0.98]` ;
  variantes `secondary`, `ghost`, `ink` (noir), `danger`, `booked` (contour émeraude, retrait d'un créneau). Taille `touch` = 48 px.
- **Badge** : capsule, `dot` ajoute la pastille d'état. Tons `neutral`, `primary`,
  `primary-outline`, `plum`, `free`, `tight`, `full`, `danger`.
- **Carte de créneau** (tuile à jauge, maquette « Tuiles avec Jauges ») : sur-titre
  « Disponibilité » et capsule de places (ton de jauge), jauge à segments — un segment
  par place libre, barre continue au-delà de 8 places —, titre de mission, consignes,
  bouton primaire « Réserver ce créneau » pleine largeur. Réservée : `ring-2
  ring-gauge-free/50`, badge `free` « Vous participez », bouton `booked` « Se désister ».
  Bloquée : fond `zinc-50` et **motif en clair** dans une tuile grise à la place du bouton.
- **Sélecteur de jour** : commande segmentée sur fond `zinc-100`, l'actif en carte
  blanche, collé sous la barre du haut.
- **Navigation** : mobile = barre du haut (marque + compte) et **barre d'onglets fixe
  en bas** (Accueil, Planning, Profil) ; desktop = onglets en capsule au centre.

## Kit de composants Blade

Une page se compose avec `resources/views/components/ui/` (`button`, `card` avec `kicker`
et slot `icon`, `page-header`, `badge`, `alert`, `field`, `readonly-field`, `data-list`,
`data-row`, `stat`, `gauge`, `table`, `empty`, `container`, `avatar`, `brand`, `select`,
`pagination`, `confirm-form`). Le back-office ajoute les siens dans
`components/admin/` (`volunteer-filters`, `shift-roster`, `mission-form`). Si un
besoin n'a pas de composant, on ajoute le composant. La planche `/design-system` (hors
production, authentifiée) montre le kit.

## Ce que les tests interdisent

`tests/Feature/DesignSystemTest.php` échoue sur : classe `dark:`, palette Tailwind hors
tokens, hexadécimal dans une vue, `<button>`/champ en `rounded-full`, ombre non nommée,
`shadow-overlay` hors menu déroulant, modale et notification.

## Mobile first

Conçu pour 375 px d'abord : une carte de créneau par ligne, cibles tactiles ≥ 44 px,
padding bas de page pour la barre d'onglets. Grille 2 colonnes dès `md`, 3 dès `lg`.
Pas de mode sombre en MVP.
