# Pilotage des agents IA — Salon de la Danse

Guide d'utilisation pour développer le MVP avec Claude Code sans jamais recontextualiser le
projet à la main, et sans brûler du contexte inutilement.

---

## 1. Comment ça marche

Le contexte du projet vit dans un **skill**, pas dans tes prompts :

```
.claude/skills/salon-benevoles/
├── SKILL.md                      ← ~100 lignes, chargé quand le skill se déclenche
└── references/
    ├── domaine.md                ← chargé à la demande
    ├── modele-donnees.md         ← chargé à la demande
    ├── design-system.md          ← chargé à la demande
    └── lots.md                   ← chargé à la demande
```

Le principe est le **chargement progressif**. `SKILL.md` contient le strict nécessaire :
le projet, le périmètre MVP, la stack, les invariants, les conventions. Les quatre fichiers
de référence ne sont lus que lorsque la tâche en cours les concerne — inutile de charger le
schéma de base pour corriger un bouton, ni le design system pour écrire une migration.

**Conséquence pratique : tu n'as plus jamais à expliquer le projet.** Tes prompts se
réduisent à « fais le lot 4 ». Le cahier des charges PDF n'a plus besoin d'être relu par qui
que ce soit : tout ce qu'il contient d'utile est dans le skill, sous forme de texte.

---

## 2. La règle d'or : un lot = une session

**Un lot, une session, un `/clear` à la fin.**

Les 8 lots sont décrits dans `references/lots.md`. Chacun est dimensionné pour tenir dans un
contexte sans saturer. Enchaîner deux lots dans la même session, c'est traîner l'intégralité
du lot précédent — fichiers lus, erreurs, allers-retours — dans tout le lot suivant.

### Quand repartir d'un contexte neuf

| Signal | Action |
|---|---|
| Un lot vient d'être committé | `/clear` — systématique, sans réfléchir |
| Tu changes de nature de tâche (back → front, code → debug) | `/clear` |
| L'agent relit des fichiers qu'il a déjà lus dans la session | `/clear` |
| L'agent réintroduit un bug déjà corrigé | `/clear` immédiatement, c'est le signe que le contexte est pollué |
| Tu as changé d'avis sur l'approche en cours de route | `/clear` et repars du bon prompt |
| L'auto-compactage se déclenche en plein lot | Laisse finir la tâche en cours, commite, puis `/clear` |

### `/clear` plutôt que `/compact`

`/compact` résume la session et **garde le bruit** : les fausses pistes, les fichiers lus
pour rien, les erreurs de parcours. Entre deux lots, tu n'as besoin de rien de tout ça — le
skill et le code committé portent déjà tout l'état utile. `/clear` repart propre pour un
coût de contexte quasi nul.

Garde `/compact` pour le cas où un seul lot déborde vraiment, et seulement en fin de tâche.

---

## 3. Les prompts, lot par lot

À copier-coller tels quels. Ils sont volontairement courts : **le contexte est dans le
skill, pas dans le prompt**. Un prompt long est un prompt qui duplique le skill.

Fais précéder chaque lot d'un `/clear`.

### Lot 0 — Fondations données et thème graphique

```
Lot 0 du MVP : fondations données et thème graphique. Suis
references/lots.md, references/modele-donnees.md et
references/design-system.md. Migrations, modèles, seeders, factories,
puis les tokens Tailwind, Inter, et les écrans Breeze repris au design
system. Termine par migrate:fresh --seed et la suite de tests au vert.
```

### Lot 1 — Inscription par code d'invitation

```
Lot 1 du MVP : inscription par code d'invitation avec upload photo.
Suis references/lots.md. Attention à la transaction verrouillée sur la
consommation du code, et au verrouillage du profil après création.
```

### Lot 2 — Dashboard bénévole et fenêtre d'inscription

```
Lot 2 du MVP : dashboard bénévole et contrôle temporel des inscriptions.
Suis references/lots.md.
```

### Lot 3 — Grille de planning en lecture seule

```
Lot 3 du MVP : la grille de planning en lecture seule, mobile first.
Suis references/lots.md, references/domaine.md pour les jauges et
references/design-system.md pour les couleurs. Aucune réservation
dans ce lot, c'est le lot 4.
```

### Lot 4 — Réservation et règles métier

```
Lot 4 du MVP : réservation et règles métier. Suis references/lots.md et
references/domaine.md. Toutes les règles dans un service unique, un test
Pest par règle, et la vérification de capacité sous transaction verrouillée.
```

> Le lot le plus dense. Si tu le sens trop chargé, coupe-le en deux sessions : le service de
> règles et ses tests d'abord, l'interface de réservation ensuite.

### Lot 5 — Validation définitive et fiche récapitulative

```
Lot 5 du MVP : validation définitive du planning et fiche récapitulative
imprimable. Suis references/lots.md. Pas de génération PDF, c'est hors MVP.
```

### Lot 6 — Back-office admin, supervision

```
Lot 6 du MVP : back-office admin, partie supervision. Suis references/lots.md.
Dashboard de compteurs, recherche multi-critères, fiche bénévole. Aucune
modification de données dans ce lot.
```

### Lot 7 — Back-office admin, outrepassement et exports

```
Lot 7 du MVP : back-office admin, outrepassement et exports Excel/CSV.
Suis references/lots.md. Dernier lot du MVP.
```

---

## 4. Prompts d'appoint

À l'intérieur d'un lot, quand ça ne se passe pas comme prévu.

**Un test échoue**

```
Ce test échoue : <nom du test>. Lis-le, corrige la cause, ne modifie pas
le test pour le faire passer.
```

**Une règle métier est mal appliquée**

```
La règle « <la règle> » ne s'applique pas correctement quand <le cas>.
Regarde le service de règles, corrige, ajoute le test qui manquait.
```

**Reprendre un lot après un `/clear` involontaire**

```
On est au lot N, partiellement fait. Fais le point sur git diff et
references/lots.md, dis-moi ce qui reste avant de coder.
```

**Fin de lot**

```
Le lot N est terminé. Lance la suite de tests, puis commite avec un
message qui décrit le lot.
```

**Revue avant de passer au lot suivant**

```
/code-review
```

---

## 5. Économiser le contexte

Ce qui coûte cher, par ordre d'impact décroissant.

**Ne jamais recoller le cahier des charges.** Le PDF fait 3 pages denses. Le skill le
remplace intégralement. Si une information manque au skill, ajoute-la au skill — une fois —
plutôt que de la répéter à chaque session.

**Ne jamais faire relire une image.** `.claude/image.png` pèse 1,6 Mo : une capture d'écran
coûte l'équivalent de plusieurs milliers de mots à chaque lecture, et il faudrait la relire à
chaque session. Le skill dit explicitement aux agents de l'ignorer. Même principe pour toute
maquette future : convertis-la en texte une bonne fois, puis oublie le fichier image.

**Cibler les tests.** `php artisan test --filter=NomDuTest` pendant le développement, la
suite complète seulement en fin de lot. Une suite complète en échec produit des centaines de
lignes dont deux sont utiles.

**Éviter les commandes à sortie massive.** `php artisan route:list` sans filtre, un
`git diff` sur tout le dépôt, un `npm install` verbeux : autant de contexte consommé pour
rien. Préfère `route:list --except-vendor --path=planning`, `git diff --stat`.

**Décrire les fichiers, ne pas les coller.** Donne un chemin, l'agent lit ce dont il a
besoin. Coller 200 lignes de Blade dans le prompt, c'est payer deux fois.

**Laisser le skill faire son travail.** Si tu te surprends à réexpliquer les créneaux, les
missions ou les quotas dans un prompt, c'est que le skill devrait déjà le dire. Corrige le
skill.

**Une question ≠ une session.** Pour une question ponctuelle sans rapport avec le lot en
cours, ouvre une session à part plutôt que de polluer celle du lot.

---

## 6. Faire vivre le skill

Le skill est un fichier du dépôt, versionné, partagé avec ton binôme. Il doit évoluer.

**Mets-le à jour quand** une décision d'architecture est prise, une convention émerge, un
point ouvert est tranché par le client, ou le périmètre du MVP bouge.

**Où écrire quoi** :

| Information | Fichier |
|---|---|
| Invariant, convention, périmètre | `SKILL.md` |
| Règle métier, donnée de référence | `references/domaine.md` |
| Table, colonne, contrainte | `references/modele-donnees.md` |
| Couleur, police, forme, composant visuel | `references/design-system.md` |
| Découpage, critère de fin de lot | `references/lots.md` |

Garde `SKILL.md` court. C'est le seul fichier lu à chaque déclenchement : chaque ligne
ajoutée est payée à chaque session. Tout ce qui est consultable à la demande appartient aux
références.

---

## 7. Points à faire trancher

Trois ambiguïtés du cahier des charges sont listées en fin de `SKILL.md`. L'agent les
signalera s'il les rencontre, sans décider seul. À arbitrer avec le client dès que possible :

1. La tranche `8h30-10h00` dure 1h30, pas 2h comme annoncé.
2. « Logistique (Niveau 0 + -2) » : une mission ou deux ?
3. Les profils mineurs supposent une date de naissance, absente des champs requis.

**La direction artistique n'en fait pas partie : elle est tranchée.** La charte graphique du
site officiel a été écartée, `references/design-system.md` fait autorité et les agents ont
consigne de l'appliquer sans la rediscuter. Si tu veux la faire évoluer, modifie ce fichier
— ne le négocie pas dans un prompt, la décision serait perdue à la session suivante.
