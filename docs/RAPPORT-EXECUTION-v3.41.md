# RAPPORT D'EXÉCUTION — v3.41 « fix/audit-restauration-reviews »

**Mandat 4P LP : AUDIT ET CORRECTION DU SYSTÈME D'AVIS (REVIEWS & ÉTOILES)**
Branche isolée : `fix/audit-restauration-reviews` (base = main v3.40 `5f4882b`) — **merge main INTERDIT sans feu vert explicite**.
Périmètre : **2 fichiers PHP + bump version** — ZÉRO CSS, ZÉRO JS, ZÉRO gabarit visuel, ZÉRO changement du tunnel/prix/CAPI.

---

## 1. Conjoncture

Les sections d'avis des clientes (« مراجعة الزبناء ») de la landing n'affichaient plus correctement les notes et commentaires, avec un comportement perçu comme dissocié entre les avis d'étoiles simples et les commentaires textuels soumis à modération. Le mandat imposait : audit d'abord (présentation des conclusions AVANT tout code correctif), branche isolée, zéro régression de la base v3.40 verrouillée, documentation à jour, merge sous feu vert.

## 2. Audit préalable (présenté au mandataire avant tout correctif)

### 2.1 Architecture d'origine reconstituée (PROJECT_MAP + `git log -S`)

| Couche | Implémentation | Dernière modification |
|---|---|---|
| Rattachement | `abaya_reviews_target_post_id()` (functions.php:512) : `page_on_front` → `LANDING_PRODUCT_ID` → repli code 15 | v2.0 (`ce86781`) |
| Listing | gabarit `landing-offer.php` : `get_comments(['status'=>'approve','type'=>'review','number'=>50,'post_id'=>cible])` | v2.0 |
| Étanchéité cartes | `abaya_render_review_card()` : `approved===1` + texte réel (≠ vide/placeholder « تقييم بنقرة واحدة ») + rating méta 1..5 | v2.1 (`1aa6eed`) |
| Modération | `abaya_handle_submit_review()` : texte → hold ; étoiles seules + commande tunnel valide → auto-approbation | v2.x |
| Badge Global Score | `abaya_review_summary()` : union **approve + hold** (découplage v2.4 `112bcf4`) | v2.4 |
| JS / CSS | app.js (pop-up + POST + `updateRatingBadge`, listing 100 % serveur) ; charte v3.7 (`7d7b883`) | v3.7 |

### 2.2 Les « derniers correctifs de structure » (v3.39/v3.40) ne sont PAS en cause

Diff exhaustif `bd684c0..5f4882b` sur les 6 fichiers concernés : **zéro ligne** de la chaîne avis touchée (grilles tarifaires, résolution produits, titres courts, typo TYP uniquement). Le code d'affichage était fonctionnellement identique à l'époque où les avis s'affichaient.

### 2.3 Preuve par exécution (sonde `audit_reviews_v341.php`, 13 vérifications × 3 scénarios)

- **Config alignée** (constante 15, avis sur post 15) : 13/13 PASS — la chaîne entière fonctionne (section, cartes, étanchéité, badge 14/3 ≈ 4,67 avec hold compté).
- **Divergence constante 15→20** : listing vide + badge 0/0 — reproduction exacte des symptômes.
- **Divergence `page_on_front`** : même disparition silencieuse.
- Fait clé : le renderer est **sans état** ; le filtrage par post appartient **entièrement aux requêtes** listing/badge.

### 2.4 Cause racine

`abaya_reviews_target_post_id()` filtrait listing, badge ET soumission sur **un unique `post_id` déduit d'une configuration externe au dépôt** (réglage WordPress `page_on_front` / constante wp-config `LANDING_PRODUCT_ID`). Toute divergence entre cette cible et le rattachement réel des avis historiques en BDD (produit vedette v3.30, page d'accueil statique, intervention wp-config lors du déploiement v3.40 — ajout de `LANDING_PRODUCT_ID_2`) rendait **tous** les avis invisibles, sans erreur ni repli. La « dissociation » étoiles/commentaires observée est, pour sa part, le design v2.4 assumé (étoiles seules → badge sans carte ; textes → modération admin) — elle n'est pas un bug, mais elle amplifiait la perception de rupture.

## 3. Correctif (justification technique)

**Principe : rendre la récupération indépendante du post de rattachement — le type « review » (exclusif au tunnel : `abaya_handle_submit_review` écrit `comment_type='review'`) devient le seul filtre.**

1. **Listing** (`template-parts/landing-offer.php`) : suppression du filtre `post_id` — `get_comments(['status'=>'approve','type'=>'review','number'=>50])`. Tous les avis textuels approuvés redeviennent visibles quel que soit leur post, quel que soit le passé du site.
2. **Badge** (`functions.php`, `abaya_review_summary()`) : suppression du filtre `post_id` des deux requêtes (approve + hold) — toutes les notes comptent ; le garde « cible absente » est retiré avec la dépendance.
3. **Soumission INCHANGÉE** (`abaya_handle_submit_review`, `abaya_reviews_target_post_id()` conservée telle quelle) : les nouveaux avis continuent d'être rattachés à la cible courante (`comment_post_ID`), la garde `abaya_no_target` (500) et la règle du maximum restent intacts — aucune rupture de contrat REST.
4. **Étanchéité v2.4 = garde unique du rendu** : inchangée (approuvé + texte réel + rating méta). Les étoiles seules n'apparaissent toujours JAMAIS comme cartes ; le listing continue d'exiger la validation admin.
5. Docblocks mis à jour (rôle du post_id réduit au rattachement) ; `style.css` bump **3.41**.

**Alternative écartée** : multi-cibles `post__in` (page_on_front + constantes + 15) — couvre l'historique connu mais réintroduit une liste fragile dépendante de la config serveur ; la requête par type est plus simple, définitivement insensible aux changements de wp-config/réglages, et l'étanchéité v2.4 filtre tout résidu éventuel.

## 4. Validation — 285 vérifications vertes

| Suite | Résultat |
|---|---|
| `php -l` ×5 + `node --check` ×3 | 0 erreur |
| **`test_reviews_v341.php` (NOUVEAU) ×4 scénarios** | **43/43** (11+11+11+10) — listing SANS post_id, badge 4 notes / 4,25 tous scénarios, étanchéité 2 cartes exactes, avis post 20 restauré, soumission rattachée à la cible + garde `no_target` conservée |
| `test_tunnel_v340.php` (replay) | 53/53 |
| `test_merci_v340.php` (replay) | 22/22 |
| `test_dissociation_v340.php` (replay) | 25/25 |
| Sonde `test_products_v340.php` ×5 configs (replay) | 5/5 |
| `test_pricing_v340.py` (replay, harnais reconstruit depuis la branche) | 27/27 — géométrie == référence v3.39 |
| `test_validation_v335.py` (replay) | 35/35 |
| `check_parity_v338.py` (replay) | 17/17 |
| `test_size_bug_v331.py` (replay) | 41/44 = baseline identique (3 écarts préexistants v3.36 documentés) |
| `test_images_v334.py` (replay) | 13/13 |
| Banc CLS `test_cls_v340.py` (replay) | 4/4 — 0,000 == témoin v3.39 |

## 5. Notes opérationnelles (hors code)

- **LiteSpeed cache** : le listing est rendu serveur — après validation admin d'avis en attente, une purge de cache de la landing peut être nécessaire pour l'affichage immédiat (comportement inchangé par ce correctif).
- **wp-config** : AUCUNE modification requise — le correctif rend l'affichage indépendant de `LANDING_PRODUCT_ID`/`LANDING_PRODUCT_ID_2`/`page_on_front` ; ces réglages conservent leur rôle produits (v3.40) et de rattachement des nouveaux avis.
- Les avis historiques rattachés à d'anciens posts (produit vedette v3.30, landings retirées) réapparaissent automatiquement s'ils satisfont l'étanchéité v2.4 (approuvés + texte + rating méta).

## 6. Statut

Branche `fix/audit-restauration-reviews` poussée sur origin. **Merge main EN ATTENTE du feu vert explicite du mandataire** (protocole 4P LP).
