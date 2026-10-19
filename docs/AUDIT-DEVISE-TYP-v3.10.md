# Rapport d'audit comparatif — Positionnement de la devise « درهم » (mandat 4P LP — v3.10)

**Périmètre** : Thank You Pages (Principale + 3 Secondaires — gabarit unique `page-merci.php`)
**Référence** : Landing Page Principale (affichage validé « 299 درهم »)
**Screens client** : ordre #86 — récapitulatif « درهم 299 » + upsell « درهم 299 » / carte centrale « درهم299 » (collée)

---

## 1. Méthode exacte de la LP Principale (référence correcte)

La LP affiche « 299 درهم » par **texte brut « montant + espace + درهم » posé dans le contexte RTL de la page, sans aucun wrapper directionnel** :

| Point | Code |
|---|---|
| Prix courant carte formulaire | `template-parts/landing-offer.php:83` — `<?= (int) $cat['bundles'][1] ?> <small>درهم</small>` |
| Total récapitulatif formulaire | `template-parts/landing-offer.php:128` — `<?= (int) $cat['bundles'][1] ?> درهم` |
| Prix des cartes d'offres | `template-parts/landing-offer.php:215` — `<?= (int) $bundle_price ?> <small>درهم</small>` |
| Mise à jour dynamique | `assets/js/app.js:125` — `` `${price} درهم` `` |

L'algorithme bidi Unicode dans une page `dir="rtl"` place le **nombre** (séquence EN, niveau 2) **à droite** et le **mot درهم** (séquence AL, niveau 1) **à gauche du montant**. Lecture arabe naturelle : « 299 درهم » — le nombre est lu en premier, la devise suit à sa gauche. C'est le rendu validé à répliquer.

## 2. Cause exacte du décalage sur les Thank You Pages

**Ce n'est NI un conflit de traduction WooCommerce, NI une concaténation PHP inversée** — l'ordre logique produit par la closure `$abaya_dh` (v3.8) est déjà `montant . ' درهم'` (correct). Le coupable est le **forçage directionnel LTR** ajouté autour :

1. **HTML** — `page-merci.php:391/396/460` : les 5 unités de prix (ligne récap + unité `/ القطعة` + total المجموع الإجمالي + prix/ancien prix upsell) sont enveloppées dans `<bdi dir="ltr">`.
2. **CSS** — `assets/css/styles.css:2070` (v3.9) : `.merci-price bdi, .merci-upsell-price bdi { direction: ltr; unicode-bidi: isolate; }` — double verrou.

L'isolation **LTR** inverse l'ordre visuel par rapport au contexte RTL : montant à **gauche**, درهم à **droite** → la devise est **lue en premier** (« درهم 299 »), l'exact inverse du rendu LP. Le bdi avait été introduit (v3.8) pour « stabiliser » l'unité de prix ; il contredit la parité LP exigée.

**Collement « درهم299 »** (carte upsell centrale, screen client) — deux vecteurs :
- la closure PHP sépare montant et درهم par un **espace sécable** U+0020 ;
- le **chemin JS n'a jamais été aligné** : `merci-review.js:242` conserve sa propre fonction `dh = (n) => "DH " + String(n)` (préfixe latin « DH », résidu pré-v3.8) utilisée par les vues mock/generic de la TYP — toute sortie de ce chemin échappe à la charte devise.

## 3. Solution technique (identique à la LP, appliquée aux 4 TYP)

1. **Sens RTL rétabli** : `<bdi dir="ltr">` → `<bdi dir="rtl">` (5 points PHP + 3 points JS) et CSS v3.10 `direction: rtl; unicode-bidi: isolate` (la règle v3.9 est neutralisée par cascade — section v3.10 en fin de feuille). Le `<bdi>` est conservé : il **isole** toujours l'unité de prix (chiffres occidentaux stables, pas de pollution bidi adjacente) mais dans le sens RTL → rendu visuel `[درهم][299]` = identique LP.
2. **Espace insecable** : closure PHP `return $abaya_text . "\u{00A0}درهم";` et JS `dh = (n) => String(n) + "\u00A0درهم"` — fin du collement « درهم299 » quel que soit le chemin de rendu.
3. **Zéro touch LP** (déjà conformes), **zéro touch** garanties v3.9 / footer mobile v3.8-v3.9 / charte avis v3.7.
4. **Hors périmètre (déjà corrects)** : « خصم 20 درهم » du bloc cadeau (ordre arabe naturel), code promo `<code dir="ltr">` (latinal voulu), téléphone `<dd dir="ltr">`.

## 4. Couverture des 4 Thank You Pages

Les 4 TYP (origine vedette `accueil` + `produit-1/2/3`) partagent le **gabarit unique** `page-merci.php` : une correction couvre les 4 pages. La validation v3.10 rend le harnais pour **chacune des 4 origines** (`ABAYA_HARNESS_OFFER`) et mesure le rendu à 1280 px et 390 px : devise à gauche du montant sur la ligne récap, le total, l'unité, les prix et anciens prix upsell.

## 5. Plan de validation

- Statique : `validate_offers.py` recalibrée (versions 3.10, S23.1/S23.3/S24.9 devise rescopés) + **bloc S25 nouveau** (12 invariants v3.10 : NBSP, dir=rtl ×8, cascade CSS, chemin JS aligné, LP intacts, maintiens v3.9/v3.7, docs).
- Visuel/DOM : `test_devise_rtl_v310.py` nouveau (4 TYP × 1280/390, mesure Range : bord droit de درهم ≤ bord gauche du montant, gap anti-collement) + rescopage v38/v39/canva (ordre inversé légitimement par le mandat).
- Régressions complètes + preuves `download/v310_*.png` + vérification GitHub API post-push.
