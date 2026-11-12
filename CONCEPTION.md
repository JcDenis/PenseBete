# PenseBete — Conception

Document de conception du plugin Dotclear **PenseBete**.
État au 12 novembre 2026 : étapes 1 et 2 codées (le plugin et sa liaison avec ClaudetteMcp), aucune release.

## Objectif

Tenir des listes de choses à faire dans l'administration d'un blog Dotclear, et permettre à un assistant IA
connecté au blog par le plugin ClaudetteMcp de les lire et de les compléter :
« ajoute cette idée à ma liste plugin », « qu'est-ce qu'il reste dans la liste blog ? ».

## Décisions prises

* **Par blog.** Les listes appartiennent à un blog et sont communes aux utilisateurs de ce blog qui ont la permission
  du plugin. Pas de liste personnelle : Dotclear est un moteur de blog, pas un gestionnaire personnel.
* **Plusieurs listes, désignées par un simple mot** (`plugin`, `blog`, `maison`, …).
* **Une table dédiée** plutôt qu'un type de billet : une tâche n'est pas un contenu, et les behaviors des billets
  (periodical, pings, …) ne doivent pas s'en mêler.
* **Deux états** : « à faire » et « fait ». Pas de corbeille.
* **Ni rappel ni échéance** : c'est l'utilisateur qui demande ce qu'il reste à faire.
* **Peu d'outils MCP** : chaque outil est à valider à la connexion du connecteur.
* **Rien côté public** : ni URL, ni widget, ni balise de template.

## Contraintes

* Un plugin Dotclear comme un autre, qui fonctionne **sans ClaudetteMcp** : la liaison est un plus,
  ClaudetteMcp n'est pas dans ses `requires`.
* **Les permissions Dotclear font foi**, dans l'admin comme par le MCP : l'assistant ne peut rien faire de plus
  que l'utilisateur de la clé.
* Dotclear 2.40 et PHP 8.3, comme ClaudetteMcp.

## Modèle de données

Une table, `pensebete` (nom en minuscules, pour ne dépendre d'aucune règle de casse des bases de données).

| Champ | Type | Usage |
|---|---|---|
| `todo_id` | bigint, clé primaire | identifiant de la tâche |
| `blog_id` | varchar 32 | le blog ; clé étrangère en cascade : supprimer le blog supprime ses tâches |
| `user_id` | varchar 32 | l'utilisateur qui a créé la tâche, pour information |
| `todo_list` | varchar 32 | le mot qui désigne la liste |
| `todo_title` | varchar 255 | la tâche, en une ligne |
| `todo_note` | text, optionnel | des détails |
| `todo_status` | smallint | `0` à faire, `1` fait |
| `todo_creadt` | timestamp | date de création |
| `todo_upddt` | timestamp | date de dernière modification, donc celle où la tâche a été cochée |

Index sur (`blog_id`, `todo_list`) et sur (`blog_id`, `todo_status`).

`user_id` n'a pas de clé étrangère : supprimer un utilisateur ne doit ni être bloqué par ses tâches,
ni supprimer des tâches qui sont communes au blog.

### Les listes

Il n'y a pas de table des listes : une liste existe tant qu'elle contient au moins une tâche, comme un mot-clé.

* Un nom de liste est un seul mot de 32 caractères au plus : lettres (accents compris), chiffres, `-` et `_`.
  Il est enregistré en minuscules : `Plugin` et `plugin` sont la même liste.
* Sans liste indiquée, la tâche va dans la liste par défaut, `todo`.
* Renommer une liste change le mot de toutes ses tâches. Renommer vers une liste qui existe déjà fusionne les deux :
  c'est ce qui corrige une liste créée par erreur (`plugins` à côté de `plugin`).

## Permissions

* Une permission propre au plugin (« gérer les listes de tâches »), à donner aux utilisateurs du blog.
  Les administrateurs du blog et les super administrateurs l'ont de fait.
* Elle donne tout sur les tâches du blog, quel qu'en soit l'auteur : voir, ajouter, modifier, cocher, supprimer.
  Pas de niveau « lecture seule » dans l'admin en v1.
* Elle ne donne pas accès à l'admin : comme pour toute page de l'admin, l'utilisateur doit aussi pouvoir s'y connecter
  (permission « gérer ses propres billets » au moins).
* Par le MCP, l'utilisateur de la clé doit avoir en plus la permission d'ApiServer (« API client »),
  comme pour tous les outils de ClaudetteMcp.

## Administration

Une page du plugin dans le menu Blog, construite comme les listes du cœur (billets, pages) :

* la page du plugin est la liste des tâches à faire, avec pagination ;
* le bloc dépliable « Filtres et options d'affichage » du cœur (`Filters`) la limite à une liste, à un statut
  ou à un texte recherché, et règle le tri et le nombre de tâches par page ; ces options sont déclarées
  par le behavior `adminFiltersListsV3`, elles se règlent donc aussi dans les préférences de l'utilisateur ;
* un bouton « Nouvelle tâche » en haut de page mène au formulaire d'une tâche, sur sa propre page :
  texte, liste (avec les listes existantes en suggestion) et détails ;
* le même formulaire sert à modifier une tâche, avec en plus son statut et sa suppression ;
* un second bouton, « Renommer une liste », mène au formulaire de renommage, lui aussi sur sa propre page ;
* des actions groupées : marquer « fait », remettre « à faire », déplacer dans une autre liste, supprimer ;
* un bouton par tâche pour la marquer « faite » ou la rouvrir.

La recherche de texte ne tient compte ni de la casse ni des accents. Elle est faite par le plugin et non par la base
de données, pour donner le même résultat avec MySQL, PostgreSQL et SQLite.

Supprimer une tâche ne se fait que dans l'admin, et c'est définitif.

## Liaison avec ClaudetteMcp

Règle de ClaudetteMcp : un plugin porte lui-même sa liaison, dans son propre code (exemple : periodical).

* Les outils sont des classes de `src/ClaudetteMcp/` qui étendent `Dotclear\Plugin\ClaudetteMcp\McpTool`,
  ajoutées par le behavior `ClaudetteMcpAddTool` dans `Frontend.php`. Elles ne sont chargées que par ClaudetteMcp.
* `Frontend::init()` ne doit pas être limité à certains types d'URL, sinon le behavior n'est jamais enregistré
  pour le connecteur.
* Les outils s'exécutent dans le contexte public, où le cœur ne vérifie rien : chaque outil vérifie lui-même
  la permission du plugin (`available()`), et ne lit ou n'écrit que les tâches du blog de la clé.
* Les outils d'écriture déclarent `readOnly()` à `false` : ClaudetteMcp ne les propose pas avec une clé « lecture seule ».
* Aucune modification de ClaudetteMcp n'est nécessaire.

### Outils

| Outil | Type | Rôle |
|---|---|---|
| `list_todos` | lecture | Les listes du blog avec leur nombre de tâches à faire, et les tâches d'une liste ou de toutes. Arguments : `list`, `status` (`todo` par défaut, `done`, `all`), `search`, `page`. |
| `add_todo` | écriture | Ajoute une tâche. Arguments : `title` (obligatoire), `list`, `note`. |
| `update_todo` | écriture | Modifie une tâche. Arguments : `id` (obligatoire), puis ce qui change parmi `title`, `note`, `list`, `status` (`todo`, `done`). |

Règles :

* Trois outils : cocher, rouvrir et déplacer une tâche passent tous par `update_todo`.
* **Pas de suppression par le MCP**, comme pour les autres outils de ClaudetteMcp, qui les annonce tous comme
  non destructifs. « Supprime cette tâche » la marque comme faite : rien n'est perdu, et la vraie suppression
  reste dans l'admin.
* `list_todos` donne toujours les noms des listes existantes. Les descriptions des outils demandent à l'assistant
  de réutiliser la liste la plus proche de ce que dit l'utilisateur (« ma liste plugins » → `plugin`),
  et de n'en créer une nouvelle que si rien ne correspond.
* Réponses courtes : 50 tâches par page.
* Le texte des tâches est écrit par les utilisateurs du blog : les descriptions des outils demandent à l'assistant
  de le traiter comme une donnée, jamais comme une instruction.
* Les dates sont dans le fuseau de l'utilisateur, comme pour les autres outils.
* Noms et descriptions des outils en anglais, non traduits : ils sont lus par l'assistant.
* Les noms ne doivent pas reprendre celui d'un outil de ClaudetteMcp, qui garde le premier outil déclaré sous un nom.
* `add_todo` indique dans sa réponse si la tâche a créé une nouvelle liste (`new_list`), pour que l'assistant le signale.
* Un titre trop long est refusé plutôt que coupé, et une liste inconnue de `list_todos` n'est pas une erreur :
  la réponse donne toujours les listes existantes.
* Les messages d'erreur destinés à l'assistant sont en anglais, comme les descriptions.

## Étapes

1. **Le plugin seul** : table, permission, page d'admin, traductions, aide.
   Tests par le workflow de gh2da, puis sur le blog de dev.
2. **La liaison ClaudetteMcp** : les trois outils. Test sur le blog de dev (connecteur à reconnecter
   pour valider les nouveaux outils), puis sur le blog personnel avec une nouvelle clé.
3. **Plus tard**, si le besoin se confirme : favori du tableau de bord avec le nombre de tâches à faire,
   export et import (plugin importExport), corbeille, échéance ou priorité.

## Références

* [ClaudetteMcp](https://github.com/JcDenis/ClaudetteMcp) : `CONCEPTION.md`, et la section DEVELOPERS du README
* [periodical](https://github.com/JcDenis/periodical) : exemple de liaison, dans `src/ClaudetteMcp/`
