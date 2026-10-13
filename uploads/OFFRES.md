# Pages Produit 1, 2 et 3

Les pages WordPress `produit-1`, `produit-2` et `produit-3` sont créées au prochain chargement de l'administration après installation du thème. Chaque slug utilise son gabarit `page-produit-*.php` et sa configuration dédiée dans `inc-offers.php`. La structure du tunnel reste commune.

## Produits WooCommerce

Définir dans `wp-config.php` les identifiants réels des produits si chaque page doit créer une ligne de commande distincte :

```php
define('LANDING_PRODUCT_ID', 15);   // repli historique / Produit 1
define('LANDING_PRODUCT_ID_1', 15);
define('LANDING_PRODUCT_ID_2', 42);
define('LANDING_PRODUCT_ID_3', 43);
```

Remplacer les exemples par les ID WooCommerce de la boutique. Sans les constantes 2 et 3, ces pages utilisent volontairement le produit historique mais leurs commandes restent identifiables par `_abaya_offer_id`, `_abaya_offer_name` et les métadonnées visibles de l'article. Le Pixel navigateur et le CAPI utilisent l'ID effectif de la page.

## Fichiers indépendants

- Textes, slogans et association produit : `inc-offers.php`.
- Photos WebP et vignettes : `uploads/offers/produit-1/`, `uploads/offers/produit-2/`, `uploads/offers/produit-3/`.
- Note moyenne : calcul sur les commentaires `review` approuvés en base, sans inclure les avis en attente.
- Les commandes continuent d'utiliser la route REST commune `landing/v1/create-order` ; les avis utilisent `landing/v1/submit-review`.

La page `/merci/` conserve la vérification de clé et le no-cache existants, ajoute le formulaire d'avis modéré et des liens vers les deux autres offres. Les copies des assets doivent rester dans l'arborescence du thème WordPress, pas dans le dossier statique racine du projet.

## Prévisualisation locale

Quand la route REST renvoie 404 ou échoue au niveau réseau, les modules `mock-api.js` enregistrent les commandes sous la clé `mock_orders` et les avis approuvés sous `mock_reviews` dans `localStorage`. Les marqueurs `mock_order_mode` et `mock_review_mode` activent séparément les données simulées. Une réponse HTTP réelle désactive le mock correspondant; les réponses 4xx métier restent des erreurs et ne déclenchent pas la simulation.

Le serveur statique de prévisualisation ne réécrit pas les slugs WordPress : les alias de test sont `/produit-1.html`, `/produit-2.html`, `/produit-3.html` et `/merci.html`. Sur WordPress, les URL restent les slugs `/produit-1/` à `/produit-3/` et `/merci/`. Les données mock sont locales au navigateur et ne créent jamais de commande WooCommerce.
