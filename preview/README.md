# preview/ — Prévisualisation locale (mode développement UNIQUEMENT)

Prototype HTML statique FIGÉ (état initial du dépôt) : il sert à prévisualiser
les redirections, le pop-up d'évaluation et le recalcul des étoiles quand le
serveur WordPress réel est injoignable. **Il ne fait PAS partie du thème** :
le thème est à la racine du dépôt (installable via WP Pusher).

## Utilisation

```bash
cd preview && python3 -m http.server 8080
# http://localhost:8080/            → landing principale (prototype)
# http://localhost:8080/produit-1/  → landings secondaires (alias .html en racine)
# http://localhost:8080/merci/      → page remerciement + pop-up (mode mock)
```

## Comportement du mock (`mock-api.js`)

- Commandes → localStorage `mock_orders` ; avis → `mock_reviews` (statut « approved » pour le recalcul des étoiles).
- Marqueurs `mock_order_mode` / `mock_review_mode` activés séparément.
- Une réponse HTTP réelle (2xx ou 4xx métier) désactive le mock correspondant ;
  seules les indisponibilités réseau (404/50x) basculent en simulation.
- Les données mock restent locales au navigateur : **aucune commande WooCommerce n'est créée**.

## Assets

`uploads/` contient les images référencées par ce prototype (ancienne série).
Le thème, lui, utilise `../uploads/` (racine) et `uploads/offers/produit-N/`.
