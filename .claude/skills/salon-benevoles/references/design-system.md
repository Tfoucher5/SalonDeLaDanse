# Design system

**La charte graphique du site officiel n'est pas suivie.** Décision prise avec le client le
22 septembre 2025. `.claude/image.png` est conservé à titre documentaire — ne l'applique pas,
ne le charge pas.

## L'intention

Cette plateforme est un **outil**, pas un site vitrine. Un bénévole s'y connecte deux ou
trois fois pour composer son planning ; un administrateur y passe ses journées pendant
l'événement. La direction artistique sert donc la lisibilité avant l'effet.

Le principe qui tranche tous les arbitrages : **l'interface est neutre pour que la couleur
appartienne à l'information**. Dans une grille de planning, le vert, l'ambre et le gris des
jauges sont le seul message qui compte. Un habillage coloré entrerait en concurrence avec
eux. C'est précisément ce qui condamnait la charte d'origine : tout en bordeaux, elle
rendait le rouge des jauges illisible comme signal.

L'identité vient du logo et du contenu, pas du décor.

## Palette

### Neutres — la structure

Échelle `zinc` de Tailwind, disponible sans configuration. Neutre légèrement froide, plus
nette qu'un gris pur.

| Token | Hex | Usage |
|---|---|---|
| `zinc-50` | `#FAFAFA` | Fond de page |
| `zinc-100` | `#F4F4F5` | Fonds de section, lignes alternées, états survolés |
| `zinc-200` | `#E4E4E7` | **Bordures et séparateurs** — le trait de base de l'interface |
| `zinc-400` | `#A1A1AA` | Icônes secondaires, texte désactivé |
| `zinc-500` | `#71717A` | Texte secondaire, libellés |
| `zinc-900` | `#18181B` | Texte courant et titres |
| blanc | `#FFFFFF` | Cartes, surfaces élevées |

Les cartes sont **blanches sur fond `zinc-50`**, délimitées par une bordure `zinc-200`.
C'est le contraste de surface qui structure la page, pas l'ombre.

### Accent — l'action

Un seul accent, l'indigo. Il ne sert qu'à **ce sur quoi on peut cliquer**.

| Token | Hex | Usage |
|---|---|---|
| `primary` | `#4338CA` | Boutons primaires, liens, onglet actif |
| `primary-hover` | `#3730A3` | Survol |
| `primary-ring` | `#4F46E5` | Anneau de focus |
| `primary-soft` | `#EEF2FF` | Fond de badge, ligne sélectionnée |

Indigo est choisi parce qu'il ne collisionne avec **aucune** couleur fonctionnelle : ni le
vert ni l'ambre des jauges, ni le rouge du danger. C'est la condition pour qu'un accent
reste lisible comme « action » et rien d'autre.

### Fonctionnelles — l'information

| Token | Hex | Sens |
|---|---|---|
| `gauge-free` | `#15803D` | Places disponibles |
| `gauge-tight` | `#B45309` | Presque complet |
| `gauge-full` | `#71717A` | Complet ou indisponible |
| `danger` | `#B91C1C` | Erreur de validation, action destructrice |

**« Complet » est gris, pas rouge.** Un créneau plein n'est pas une erreur, c'est un état
normal. Le rouge reste réservé à ce qui a échoué ou à ce qui détruit — un bénévole doit
pouvoir distinguer « ce créneau est pris » de « votre inscription a échoué ».

## Contrastes vérifiés

Calculés selon WCAG 2.1, sur fond blanc :

| Combinaison | Ratio | Niveau |
|---|---|---|
| `zinc-900` sur blanc | 17,7:1 | AAA |
| `zinc-500` sur blanc | 4,8:1 | AA |
| `primary` sur blanc, et blanc sur `primary` | 7,9:1 | AAA |
| `danger` sur blanc | 6,5:1 | AA |
| `gauge-free` sur blanc | 5,0:1 | AA |
| `gauge-tight` sur blanc | 5,0:1 | AA |
| `gauge-full` sur blanc | 4,8:1 | AA |

Toutes les combinaisons passent AA en texte courant. Aucune contrainte de taille minimale.

**La couleur ne suffit jamais.** Chaque créneau affiche le nombre de places restantes en
toutes lettres. L'interface doit rester utilisable en niveaux de gris — un bénévole daltonien
doit pouvoir réserver.

## Typographie

**Inter**, une seule famille, chargée depuis Google Fonts. Dessinée pour les interfaces :
lisible à 13 px, hauteur d'x généreuse, chiffres nets. Breeze est livré avec Figtree, à
remplacer.

Activer les **chiffres tabulaires** (`font-variant-numeric: tabular-nums`) sur la grille de
planning et les tableaux d'administration. Sans cela les horaires et les compteurs de places
dansent d'une ligne à l'autre — c'est le détail qui sépare une grille propre d'une grille
approximative.

| Niveau | Taille | Graisse | Couleur |
|---|---|---|---|
| Titre de page | 24-30 px | 600 | `zinc-900` |
| Titre de section | 18-20 px | 600 | `zinc-900` |
| Texte courant | 15-16 px | 400 | `zinc-900` |
| Secondaire, libellé | 13-14 px | 400-500 | `zinc-500` |

Pas de deuxième police, pas de titres en capitales, pas de lettrage étendu. La hiérarchie se
fait à la taille et à la graisse.

## Formes

| Élément | Rayon |
|---|---|
| Boutons, champs, badges | 6 px (`rounded-md`) |
| Cartes, panneaux, modales | 8 px (`rounded-lg`) |

**Pas de pilules.** Le `rounded-full` était la signature de la charte abandonnée ; un rayon
modéré et constant lit plus sérieusement.

**Bordures plutôt qu'ombres.** Une bordure `zinc-200` délimite cartes, champs et lignes de
tableau. Une seule ombre existe, douce, réservée aux éléments réellement flottants : modale
de confirmation, menu déroulant. Empiler les ombres dans une grille dense la rend sale.

## Composants

### Boutons

| Variante | Fond | Texte | Bordure |
|---|---|---|---|
| Primaire | `primary` | Blanc | — |
| Secondaire | Blanc | `zinc-900` | `zinc-200` |
| Discret | Transparent | `zinc-500` | — |
| Destructeur | Blanc | `danger` | `zinc-200` |

Hauteur 40 px, padding horizontal 16 px, graisse 500. **Un seul bouton primaire par écran** :
c'est ce qui rend l'action principale évidente. « Valider définitivement » est primaire ;
tout le reste sur cet écran ne l'est pas.

### Champs

Bordure `zinc-200`, fond blanc, 40 px de haut. Au focus : bordure `primary` et anneau
`primary-ring` de 2 px. En erreur : bordure `danger` et message sous le champ, jamais une
simple coloration — le message dit quoi corriger. Le plugin `@tailwindcss/forms` est déjà
installé.

### Carte de créneau — le composant central

C'est l'élément le plus manipulé de la plateforme, il mérite d'être soigné.

Carte blanche, bordure `zinc-200`, rayon 8 px. Elle porte : le nom de la mission en
`zinc-900`, l'horaire en `zinc-500`, et les places restantes en toutes lettres dans la
couleur de jauge correspondante.

| État | Traitement |
|---|---|
| Disponible | Bordure `zinc-200`, cliquable |
| Réservé par le bénévole | Bordure `primary`, fond `primary-soft`, coche visible |
| Complet | Fond `zinc-50`, texte `zinc-400`, non cliquable |
| Bloqué par une règle | Comme « complet », **plus le motif en clair** sous la carte |

La dernière ligne est essentielle : « Vous avez déjà 3 créneaux » est une information utile,
« indisponible » ne l'est pas. Une règle métier qui bloque doit toujours se justifier à
l'écran.

### Tableaux d'administration

Lignes séparées par une bordure `zinc-200`, en-tête `zinc-500` en 13 px graisse 500, survol
de ligne en `zinc-100`. Pas de rayures alternées : la bordure suffit et se lit mieux en
densité.

## Mise en œuvre

Tailwind **3.4.19** est la version réellement installée : le thème se déclare dans
`tailwind.config.js` sous `theme.extend`, pas via la directive `@theme` de Tailwind 4.

```js
// tailwind.config.js
theme: {
    extend: {
        colors: {
            primary: {
                DEFAULT: '#4338CA', hover: '#3730A3',
                ring: '#4F46E5',    soft: '#EEF2FF',
            },
            gauge: { free: '#15803D', tight: '#B45309', full: '#71717A' },
            danger: '#B91C1C',
        },
        fontFamily: { sans: ['Inter', ...defaultTheme.fontFamily.sans] },
    },
},
```

Les neutres passent par l'échelle `zinc` native, rien à déclarer.

**Aucune couleur en dur dans les vues Blade.** Toujours les tokens : `bg-primary`,
`text-gauge-free`, `border-zinc-200`.

**Pas de mode sombre en MVP.** Les vues Breeze arrivent truffées de classes `dark:` : les
retirer au lot 0. Un mode sombre à moitié fait est pire que pas de mode sombre, et ce n'est
pas au périmètre.

## Mobile first

Le planning se conçoit **d'abord pour un écran de 375 px**, l'écran large n'étant qu'un
élargissement. Cibles tactiles de 44 px minimum. Une carte de créneau par ligne en mobile,
grille en colonnes au-delà de `md`. Le design system donne les couleurs, les formes et la
typographie — il ne dicte pas la mise en page.
