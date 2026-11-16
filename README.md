# ClickAndMortar_Cube — statistiques de ventes pour le Cube Magento

Module Magento 2 de [Click&Mortar](https://www.clickandmortar.fr) qui expose, en lecture seule, les agrégats de ventes
du jour consommés par le **Cube Magento** (cube imprimé en 3D avec un écran : CA du jour, commandes, panier moyen,
animation et bip à chaque nouvelle commande).

- `GET /rest/V1/cm-cube/stats` : CA et commandes du jour, comparaison avec hier à la même heure, 7 derniers jours,
  objectif, record, dernière commande.
- `GET /rest/V1/cm-cube/events?since=<entity_id>` : nouvelles commandes depuis un identifiant, avec les indicateurs
  « première commande du jour » et « record battu ».

Calcul côté serveur en SQL agrégé (aucune collection chargée), sans aucune donnée personnelle : ni nom, ni e-mail,
ni adresse, seulement la ville et le nombre d'articles.

## Compatibilité

| Magento Open Source | PHP | Base | OpenSearch | Résultat |
|---|---|---|---|---|
| 2.4.6-p15 | 8.2.35 | MariaDB 10.6 | 2.19.3 | ✅ 24 tests unitaires (PHPUnit 9.6) + test d'API de bout en bout (102 assertions) |
| 2.4.8-p5 | 8.3.36 | MariaDB 11.4 | 2.19.3 | ✅ idem (PHPUnit 10.5) |
| 2.4.9 | 8.4.27 | MariaDB 11.4 | 2.19.3 | ✅ idem (PHPUnit 12.5) |

Testé le 16/11/2026 avec le banc `docker/` (Magento Open Source téléchargé depuis le miroir Mage-OS, code identique
à repo.magento.com). La 2.4.7 n'a pas été testée mais se situe entre deux versions validées. Adobe Commerce et
Mage-OS devraient fonctionner (mêmes tables `sales_order`), sans avoir été testés.

PHPCS (standard `Magento2` de magento/magento-coding-standard) : 0 erreur.

## Installation

Le module n'est pas sur Packagist : déclarer ce dépôt GitHub dans le `composer.json` de la boutique.

```bash
composer config repositories.cm-cube vcs https://github.com/ClickAndMortar/magento2-module-cube
composer require clickandmortar/module-cube:^1.0
bin/magento module:enable ClickAndMortar_Cube
bin/magento setup:upgrade
# en mode production :
bin/magento setup:di:compile && bin/magento setup:static-content:deploy -f
```

## Jeton d'accès pour le cube

Le cube s'authentifie avec un **jeton d'intégration** qui ne porte que la ressource ACL
`ClickAndMortar_Cube::stats` (« Click&Mortar Cube > Lire les statistiques du cube (API) »). Il ne peut rien lire
d'autre.

### En ligne de commande (recommandé)

```bash
bin/magento cm-cube:integration:create              # crée « Cube Magento », l'active et affiche le jeton
bin/magento cm-cube:integration:create --replace    # régénère (l'ancien jeton cesse de fonctionner)
bin/magento cm-cube:integration:create --token-only # n'affiche que le jeton, pour un script
```

### Depuis l'admin

1. Système > Extensions > Intégrations > Ajouter une intégration : nom « Cube Magento ».
2. Onglet « API » : accès aux ressources « Personnalisé », cocher seulement « Click&Mortar Cube ».
3. Enregistrer, puis « Activer » et « Autoriser » : copier le **jeton d'accès** (« Access Token »).

### Autoriser le jeton d'intégration en « Bearer »

Depuis Magento 2.4.4, un jeton d'intégration n'est accepté seul en en-tête `Authorization: Bearer` que si l'option
est activée :

```bash
bin/magento config:set oauth/consumer/enable_integration_as_bearer 1
```

ou Stores > Configuration > Services > OAuth > Consumer Settings > « Allow OAuth Access Tokens to be used as standalone
Bearer tokens » = Yes. Sans cette option, il faudrait signer chaque requête en OAuth 1.0a, ce que le firmware du cube
ne fait pas.

## Configuration

Stores > Configuration > Click&Mortar > Cube (portée globale) :

| Champ | Chemin | Défaut |
|---|---|---|
| Activer l'API du cube | `cm_cube/general/enabled` | Oui (désactivée : réponses 403) |
| Écrans actifs | `cm_cube/general/screens` | `today,compare,week,last_order,goal` |
| Objectif de CA du jour (devise de base, 0 = aucun) | `cm_cube/general/daily_goal` | 0 (l'écran `goal` disparaît alors de `screens`) |
| Fuseau horaire | `cm_cube/general/timezone` | `Europe/Paris` |
| Boutiques comptées (vide = toutes) | `cm_cube/general/store_ids` | toutes |
| États de commande exclus | `cm_cube/general/excluded_states` | `canceled` |
| Événements maximum par appel (1 à 100) | `cm_cube/advanced/events_limit` | 20 |
| Fenêtre du record, en jours (7 à 1095) | `cm_cube/advanced/record_window_days` | 365 |

## Exemples

```bash
TOKEN=…   # jeton d'intégration
curl -s -H "Authorization: Bearer $TOKEN" https://boutique.example/rest/V1/cm-cube/stats
curl -s -H "Authorization: Bearer $TOKEN" "https://boutique.example/rest/V1/cm-cube/events?since=0"
curl -s -H "Authorization: Bearer $TOKEN" "https://boutique.example/rest/V1/cm-cube/events?since=1234&limit=5"
```

### `GET /V1/cm-cube/stats`

```json
{
  "generated_at": "2026-11-16T15:08:27+01:00",
  "timezone": "Europe/Paris",
  "currency": "EUR",
  "store_ids": [],
  "screens": ["today", "compare", "week", "last_order", "goal"],
  "today": {"revenue": 220, "orders": 3, "average_order": 73.33},
  "yesterday_same_time": {"revenue": 100, "orders": 2, "average_order": 50},
  "revenue_delta_pct": 120,
  "orders_delta_pct": 50,
  "goal_revenue": 1000,
  "goal_progress_pct": 22,
  "record_revenue": 2000,
  "record_date": "2026-11-07",
  "record_broken_today": false,
  "week": [
    {"date": "2026-11-10", "revenue": 0, "orders": 0},
    {"date": "2026-11-11", "revenue": 0, "orders": 0},
    {"date": "2026-11-12", "revenue": 0, "orders": 0},
    {"date": "2026-11-13", "revenue": 1500, "orders": 1},
    {"date": "2026-11-14", "revenue": 10, "orders": 1},
    {"date": "2026-11-15", "revenue": 600, "orders": 3},
    {"date": "2026-11-16", "revenue": 220, "orders": 3}
  ],
  "last_order": {
    "entity_id": 40, "increment_id": "000000040", "amount": 70, "city": "Bordeaux", "items": 3,
    "created_at": "2026-11-16T12:06:36+01:00"
  },
  "last_entity_id": 40
}
```

| Champ | Sens |
|---|---|
| `today` | Depuis minuit (fuseau configuré) : CA = somme des `base_grand_total`, en devise de base, hors états exclus |
| `yesterday_same_time` | Hier, de minuit jusqu'à la même heure locale qu'en ce moment (journées de 23 h ou 25 h gérées) |
| `revenue_delta_pct`, `orders_delta_pct` | Écart en % avec hier à la même heure, arrondi à 0,1 ; `null` si hier = 0 |
| `goal_revenue`, `goal_progress_pct` | Objectif et avancement (peut dépasser 100) ; `null` sans objectif |
| `record_revenue`, `record_date` | Meilleur CA journalier de la fenêtre, aujourd'hui exclu ; `null` sans vente |
| `record_broken_today` | Le CA du jour dépasse déjà le record |
| `week` | 7 jours, du plus ancien à aujourd'hui (journée en cours) |
| `last_order` | Dernière commande comptée, tous jours confondus ; `null` s'il n'y en a pas |
| `last_entity_id` | Curseur initial pour `/events` |

⚠️ **Champs `null`** : Magento 2.4.6 **omet** les champs nuls du JSON, alors que 2.4.7+ les renvoie à `null`. Le
client doit traiter « absent » et `null` de la même façon.

### `GET /V1/cm-cube/events?since=<entity_id>&limit=<n>`

```json
{
  "events": [
    {
      "entity_id": 41, "increment_id": "000000041", "amount": 1400, "city": "Toulouse", "items": 1,
      "created_at": "2026-11-16T15:07:10+01:00", "first_of_day": false, "record_broken": true
    }
  ],
  "last_entity_id": 41,
  "skipped": 0
}
```

- `since=0` (premier appel) : aucune commande, seulement le curseur `last_entity_id`. Le cube ne rejoue donc pas
  l'historique au démarrage.
- Les commandes d'`entity_id > since` sont renvoyées par ordre croissant. S'il y en a plus que `limit` (défaut : la
  configuration, plafond 100), seules les **plus récentes** sont renvoyées et `skipped` compte les autres.
- Le client renvoie `last_entity_id` au prochain appel. Si `since` dépasse la dernière commande (base restaurée),
  `last_entity_id` est recalé sur la dernière commande existante.
- `first_of_day` : première commande comptée de la journée locale. `record_broken` : cette commande fait passer le CA
  du jour au-dessus du record. Les deux valent `false` pour une commande d'un jour précédent.

### Codes d'erreur

| Code | Cause |
|---|---|
| 401 | Jeton absent, invalide, ou jeton d'intégration refusé en Bearer (voir l'option ci-dessus) |
| 403 | API désactivée dans la configuration du module |
| 404 | Module absent : le cube bascule sur l'API native (ci-dessous) |

## Sans le module : repli du firmware sur l'API native

Si `GET /V1/cm-cube/stats` renvoie **404**, le cube passe en mode dégradé sur l'API REST standard :

```
GET /rest/V1/orders
    ?searchCriteria[filterGroups][0][filters][0][field]=created_at
    &searchCriteria[filterGroups][0][filters][0][value]=<minuit local converti en UTC, AAAA-MM-JJ HH:MM:SS>
    &searchCriteria[filterGroups][0][filters][0][conditionType]=gteq
    &searchCriteria[filterGroups][1][filters][0][field]=state
    &searchCriteria[filterGroups][1][filters][0][value]=canceled
    &searchCriteria[filterGroups][1][filters][0][conditionType]=neq
    &searchCriteria[sortOrders][0][field]=entity_id
    &searchCriteria[sortOrders][0][direction]=DESC
    &searchCriteria[pageSize]=50
    &fields=items[entity_id,increment_id,base_grand_total,total_qty_ordered,created_at,state],total_count
```

- Le jeton doit alors porter la ressource `Magento_Sales::actions_view` (Ventes > Opérations > Commandes > Actions >
  Voir). Cette ressource donne accès aux commandes **complètes**, données personnelles comprises. Le module évite
  précisément cela.
- Le cube fait lui-même les sommes : CA et nombre de commandes du jour, panier moyen. La requête est plafonnée à 50
  commandes ; au-delà, `total_count` donne le nombre exact mais le CA est sous-estimé, et le cube l'affiche comme tel
  (« ≥ »).
- Pas de comparaison avec hier, pas de courbe sur 7 jours, pas de record ni d'objectif. Les nouvelles commandes se
  détectent par un `entity_id` plus grand que le dernier vu, ce qui suffit pour l'animation et le bip.
- `fields=` réduit la réponse ; la ville n'est pas demandée, car elle obligerait à lire les adresses.

## Développement et tests

```bash
docker/run.sh 2.4.8 install   # Magento 2.4.8-p5 vierge + module (lié par symlink), ~5 min la première fois
docker/run.sh 2.4.8 test      # tests unitaires (cadre de Magento) + test d'API de bout en bout
docker/run.sh 2.4.6 install && docker/run.sh 2.4.6 test
docker/run.sh 2.4.9 install && docker/run.sh 2.4.9 test
docker/run.sh 2.4.8 down      # supprime la pile et ses volumes
```

- `Test/Unit` : bornes horaires (changements d'heure, fuseaux décalés d'une demi-heure), regroupement par jour local,
  écarts, record, indicateurs d'événements, configuration, services REST (base simulée).
- `Test/Api/CubeApiTest.php` : vide `sales_order` (le test refuse une base qui ne s'appelle pas `magento` ou
  `magento_test`), insère des commandes à des heures choisies (historique, hier avant et après la même heure,
  aujourd'hui, une annulée), puis vérifie chaque chiffre de `/stats` et `/events` avec un vrai jeton créé par
  `cm-cube:integration:create`. Il couvre aussi : 401 sans jeton, 403 module désactivé, filtres de boutique et d'état,
  record battu, limite d'événements, aucune donnée personnelle dans les réponses.
- Le banc 2.4.6 autorise `league/flysystem` 2.x, visé par un avis de sécurité que Composer ≥ 2.9 bloque : la
  dépendance est imposée par Magento 2.4.6 lui-même.

## Performances

- `/stats` : trois requêtes sur l'index `created_at` de `sales_order` (aujourd'hui, hier, dernière commande). L'historique
  (7 jours et record) est calculé **une fois par jour** par tranches de 15 minutes, puis mis en cache (étiquette
  `CM_CUBE`, vidé par `cache:flush`). Une commande d'un jour passé annulée après coup n'est donc prise en compte que le
  lendemain.
- `/events` : au plus `limit` commandes, plus trois agrégats.

## Licence

[OSL-3.0](LICENSE.txt), comme Magento Open Source.
