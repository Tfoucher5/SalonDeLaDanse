# Domaine métier — règles et données de référence

## Jours d'événement

| Jour | Date |
|---|---|
| Vendredi | 14 mai 2027 |
| Samedi | 15 mai 2027 |
| Dimanche | 16 mai 2027 |

## Tranches horaires

5 tranches par jour, identiques sur les 3 jours :

| # | Début | Fin |
|---|---|---|
| 1 | 08:30 | 10:00 |
| 2 | 10:00 | 12:00 |
| 3 | 12:00 | 14:00 |
| 4 | 14:00 | 16:00 |
| 5 | 16:00 | 18:00 |

> Le cahier des charges parle de « 5 créneaux de 2 heures » alors que la tranche 1 dure
> 1h30. On stocke des heures réelles ; ne jamais afficher une durée codée en dur.

## Missions

**Ouvertes à la réservation** (visibles par les bénévoles) :

1. Accueil exposants
2. Vestiaires
3. Point Info
4. Masterclass / Conférences
5. Loges danseurs
6. Logistique (Niveau 0 + -2)
7. Scène principale
8. Stand JayDance
9. Village Danses du Monde

**Sous restriction** (hors planning public, attribuées manuellement par l'admin) :

10. Billetterie
11. Caisse

Une mission restreinte n'apparaît **jamais** dans la grille du bénévole. Elle est
attribuable uniquement depuis le back-office.

## Volumétrie attendue

9 missions publiques × 3 jours × 5 tranches = **135 créneaux réservables**
(+ 30 créneaux restreints). Pour 130 bénévoles réservant 1 à 3 créneaux, prévoir une
capacité totale largement supérieure à 130.

## Règles de réservation

Ces règles s'appliquent au bénévole. L'administrateur peut toutes les outrepasser.

| Règle | Détail |
|---|---|
| **Quota minimum** | 1 créneau sur l'ensemble du week-end |
| **Quota maximum** | 3 créneaux sur l'ensemble du week-end (6h au total) |
| **Non-chevauchement** | Interdiction d'avoir 2 missions sur la même tranche horaire, quel que soit le jour concerné |
| **Pause obligatoire** | Interdiction d'enchaîner 3 tranches consécutives **le même jour** |
| **Capacité (jauge)** | Chaque créneau a une capacité maximale paramétrable. Capacité atteinte ⇒ réservation bloquée |

Le minimum de 1 créneau se vérifie **à la validation définitive**, pas à chaque ajout : un
planning en brouillon peut être vide. Les quotas min et max sont paramétrables par édition,
les valeurs ci-dessus sont les valeurs par défaut.

### Enchaînement de 3 tranches consécutives

Les tranches sont consécutives au sens de leur numéro d'ordre dans la journée. Réserver les
tranches 2, 3 et 4 d'un même jour est **interdit**. Les tranches 2, 3 puis 5 sont autorisées.
Des tranches consécutives sur des **jours différents** ne sont pas concernées.

## Code couleur des jauges

Calculé sur le taux de remplissage d'un créneau :

| Couleur | Condition |
|---|---|
| **Vert** | Places disponibles |
| **Orange** | Presque complet |
| **Gris** | Complet, ou indisponible pour ce bénévole |

Le cahier des charges écrit « Rouge/Gris ». On retient le **gris** : un créneau plein n'est
pas une erreur, et le rouge reste réservé aux échecs et aux actions destructrices. Les
valeurs exactes sont dans `design-system.md`.

Un créneau est « indisponible » — et non « complet » — quand il est plein **ou** quand le
réserver violerait une règle métier (quota atteint, chevauchement, 3 consécutifs). Dans les
deux cas le bénévole ne peut pas cliquer, mais le message d'explication diffère.

## Confidentialité

Sur un créneau, un bénévole voit **uniquement le nombre de places restantes**. Jamais les
noms, prénoms ou nombre de participants nominatifs. Cette règle vaut pour l'interface, les
réponses JSON et tout export accessible à un bénévole.

## Cycle de vie du planning bénévole

```
brouillon  ──(« Valider définitivement » + confirmation)──>  validé / verrouillé
   ↑                                                              │
   └──────────────── déverrouillage par un administrateur ────────┘
```

- **Brouillon** : le bénévole ajoute et retire librement ses créneaux.
- **Validation** : bouton « Valider définitivement », précédé d'une pop-up de confirmation.
  Vérifie le quota minimum. Après validation, le planning est en lecture seule.
- **Verrouillé** : seul un administrateur peut encore modifier.

## Fenêtre temporelle d'inscription

L'administration définit une date d'ouverture et une date de fermeture des inscriptions au
planning. En dehors de cette fenêtre, le planning passe en **consultation seule**. Un
**verrouillage manuel global** doit pouvoir être déclenché à tout moment, indépendamment
des dates.

Cette fenêtre concerne la composition du planning. Elle est distincte de la création de
compte, qui dépend du code d'invitation.

## Contenu du dashboard bénévole

Écran d'accueil après connexion, avant d'entrer dans le planning :

- Règles d'inscription et règles d'engagement
- Dates du Salon
- Quota de créneaux (minimum / maximum)
- Coordonnées de l'équipe organisatrice
- État de son planning : brouillon, validé, ou inscriptions fermées

## Back-office administrateur (périmètre MVP)

**Supervision** — compteurs temps réel : nombre total de bénévoles, comptes créés, plannings
validés vs en attente, taux de remplissage par jour et par mission.

**Recherche multi-critères** : nom, prénom, mission, statut de validation, jour.

**Outrepassement** : modifier un planning verrouillé, forcer l'attribution d'un poste
sensible (Billetterie, Caisse), réinitialiser les identifiants d'un bénévole, modifier les
informations personnelles verrouillées.

**Exports** : export global ou filtré aux formats **Excel et CSV** (planning général, liste
par mission, fiches contact). L'export PDF est hors MVP.
