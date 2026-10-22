# AUDIT — RESTAURATION DE LA GRILLE DES 3 OFFRES (v3.25)

**Mandat 4P LP « protocole mandat 4P LP » — réparation d'urgence**
**Branche : `fix/restore-offers-grid` — Version thème : 3.25 — Date : 2026-10-23**

---

## 1. Conjoncture et audit Git chirurgical

La tentative de modification précédente (commit **`76444df`**, v3.23 « single-upsell cleanup »,
mergé `1e3763b`) a provoqué la régression critique signalée : la section
**« عروض خاصة حصرية »** n'affichait plus qu'**UNE SEULE carte d'offre isolée**
(قطعة واحدة / 299 درهم, cf. capture client) au lieu de la grille historique des
**3 offres côte à côte** (1 pièce / 2 pièces / 3 pièces), brisant le tunnel de
conversion.

L'audit (`git diff 0dae11e..1e3763b` — v3.22 → v3.23) établit les faits suivants :

| Fichier | Modification v3.23 (destructrice) | Nature |
|---|---|---|
| `inc-offers.php` | grille `[1=>299, 2=>499, 3=>699]` → `[1=>299]` ; labels ×3 → ×1 ; entrées `produit-2`/`produit-3` SUPPRIMÉES ; ajout `abaya_retired_offers()` | **mutilation data-driven** |
| `functions.php` | gate 301 permanente produit-2/3 → racine | rerouting |
| `page-merci.php` | docblock + bloc CSS `:only-child` (upsell TYP unique centré 340px) | présentation |
| `assets/js/app.js` | replis `{1:299}` + labels ×1 | replis |
| `assets/css/styles.css` | section v3.23 `.bundle-card:only-child` (centrage carte unique) | présentation |
| `template-parts/landing-offer.php` | **AUCUNE retouche** | — |
| `assets/css/offers.css` | **AUCUNE retouche** | — |

**Conclusion de l'audit** : la grille n'a pas été « recodée » — elle a été *asphyxiée
par les données* (registre réduit). La boucle du gabarit unique est data-driven :
restaurer le registre restaure bit-exact le rendu historique. **Aucun recodage n'a
été effectué** (conformité stricte à l'interdiction du mandat) : le revert est un
**`git checkout 0dae11e` bit-exact** des fichiers impactés.

## 2. Revert chirurgical exécuté (branche `fix/restore-offers-grid`)

| Fichier | Opération | Résultat |
|---|---|---|
| `inc-offers.php` | `git checkout 0dae11e --` (bit-exact) | bundles `[1=>299, 2=>499, 3=>699]` + labels `قطعة واحدة / قطعتان / 3 قطع` + économies `50/99/198 درهم` + entrées produit-2/produit-3 réintégrées ; `abaya_retired_offers()` DISPARUE |
| `functions.php` | `git checkout 0dae11e --` (bit-exact) | gate 301 supprimé ; gate 302 historique (isolation upsell) inchangé |
| `page-merci.php` | `git checkout 0dae11e --` (bit-exact) | docblock « EXACTEMENT 3 produits » + bloc v3.23 `:only-child` supprimés — TYP multi-upsell « offres restantes » (3 cartes) |
| `assets/js/app.js` | `git checkout 0dae11e --` (bit-exact) | replis `{1:299, 2:499, 3:699}` + labels ×3 (jamais exécutés en production — `window.LANDING` injecté) |
| `assets/css/styles.css` | chirurgie ciblée : suppression de la SEULE section v3.23 (lignes 2394-2414) | fin de feuille = `fin v3.22` ; **section v3.24 (pilule back-link `.legal-home-link`) INTACTE** |
| `style.css` | bump manuel `3.24` → `3.25` | version mandat |
| `template-parts/landing-offer.php`, `offers.css` | aucun changement nécessaire (jamais touchés par v3.23) | markup/CSS historiques préservés |

**Vérification d'intangibilité préalable** : `git log 0dae11e..0fa987a -- <fichier>`
a prouvé qu'entre v3.22 et HEAD, seuls les commits identifiés (`76444df` v3.23,
`2db7d24` v3.24) avaient touché ces fichiers — le checkout bit-exact est donc
sûr et complet. Le travail v3.24 (boutons de retour) vit dans des fichiers non
restaurés (`template-parts/legal-header.php`, `site-header.php`, `offers.css`)
et dans la section v3.24 de `styles.css` : **il n'a jamais été menacé par le revert**.

## 3. Auto-audit fonctionnel — suite dédiée `test_restore_offers_v325.py` : 47/47 PASS

### 3.1 Présence des 3 offres (desktop 1280 + mobile 360)
- **A1/C1** — 3 cartes `.bundle-card` présentes sur la vedette (desktop **et** mobile 360) et sur les landings restaurées produit-2/produit-3 (**D1** ×2).
- **A2/A3** — disposition HORIZONTALE historique : 3 cartes sur la même rangée
  (±3px — la carte sélectionnée porte `translateY(-2px)`), largeurs équilibrées ~1/3.
- **C3** — mobile 360 : **grille 3 colonnes FIXES** (3 cartes côte à côte, v3.17),
  **C2** zéro débordement horizontal.
- **A4** — labels restaurés : `قطعة واحدة` / `قطعتان` / `3 قطع`.
- **A5** — prix : **299 / 499 / 699 درهم**. **A6** — économies : `توفير 50/99/198 درهم`.
- **A7** — ruban `الأكثر طلباً` réapparu sur la carte 2 (pur CSS `::after`, fond émeraude calculé).

### 3.2 Interactivité du tunnel (mandat PARTIE 3 §2) — clic sur CHACUNE des 3 offres
Pour chaque offre 1, 2 et 3 (**B1.1→B3.5**, à 1280px) :
1. clic → `.is-selected` SUIT la carte cliquée (`dataset.quantity` correct) ;
2. contour d'état sélectionné mesuré : `borderColor == rgb(58, 125, 132)` (émeraude CTA) ;
3. `aria-pressed` synchronisé (`false/false/true` selon la carte) ;
4. récap prix mis à jour : **299 → 499 → 699 درهم** ;
5. **défilement automatique vers le formulaire** prouvé (formulaire visible dans le viewport après clic).

Mobile 360 : clic offre 2 → récap **499 درهم** (**C4**) + scroll formulaire (**C5**).

### 3.3 Autorité serveur (grille restaurée) — suites REST/tunnel
- `test_offers_rest.php` 30/30 : qty 2 sur produit-2 → **succès 499** ; qty 3 sur produit-3 → **succès 699** ; qty 4 clampée à 3 → **succès 699** (comportement socle historique) ; whitelist couleur/taille par offre inchangée (422) ; registre = **4 offres** (T6.2) ; 3 pages secondaires planifiées (T7.1).
- `test_post_purchase.php` 62/62 : gate 302 restauré sur produit-2/produit-3 (visite directe → 302 vedette ; duo valide → rendu + no-cache — **fin des 301**) ; `add-to-order` produit-2 qty 2 → 499, total recalculé 798, clamp qty 7 → 3 → 699 ; TYP : **EXACTEMENT 3 cartes upsell** pour une commande sans upsell, offres déjà ajoutées EXCLUES (2 cartes restantes après achat produit-2), déverrouillage order+key intact.

### 3.4 Conservation exacte du bouton de retour « ‹ رجوع »
- `test_back_link_v324.py` : **86/86 PASS** (rejoué sur le thème restauré) — pilule
  « ‹ رجوع » une ligne, miroir bidi, destinations (réglementaires → Accueil `/`,
  Upsell → TYP personnalisée `merci_url`), **logo 40px inchangé**, racine par défaut inchangée.
- Statique (**F7/F10/E3**) : pilule v3.24 `border-radius: 50px` + markup
  `back-arrow/back-text` présents à l'identique dans `styles.css`/`legal-header.php`.
- Isolation tunnel : sur `/merci/` nue, AUCUN lien retour (design v2.5 inchangé — **E2**).

### 3.5 ZÉRO régression sur le reste de la page
Batterie complète **`run_regressions_v325.sh` : 28 suites / 1697 PASS / 0 FAIL** —
dont espacement v3.21 (146), légal v3.20 (81+12), mobile v3.17/318/319 (96),
modales v3.15/316 (152), TYP redesign (29), avis v3.7 (64), logo v3.11-13 (201),
devise v3.10 (53), chartes visuelles v3.8/v3.9 (57), Canva (41), lp-variants (74),
badge (16), popup (14), shots v3.5/v3.6 (77), REST commandes (30) + avis (29),
tunnel (62), statique S1-S36 (**330**). `php -l` ×3 + `node --check` OK.

## 4. Preuves visuelles (auto-audit mobile 360px / desktop 1280px)

| Capture | Contenu prouvé |
|---|---|
| `download/v325_lp_restaurée_1280.png` | LP vedette desktop : 3 cartes côte à côte (699/499/299), ruban الأكثر طلباً, ✓ sélection, reste de la page intact |
| `download/v325_lp_restaurée_360.png` | LP vedette mobile 360 : grille 3 colonnes horizontale, zéro débordement |
| `download/v325_produit2_restaurée_1280.png` | Landing produit-2 réactivée (fin du 301) : 3 cartes |
| `download/v325_merci_390.png` | TYP mobile : 3 cartes upsell empilées 100% (verrou v3.19), header isolé inchangé |

Les captures section (`restore_offers_desktop_1280.png`, `restore_offers_mobile_360.png`)
complètent le zoom sur la grille restaurée.

## 5. Intangibilité — checklist du mandat

| Exigence du mandat | Statut |
|---|---|
| Interdiction de recoder un nouveau bloc offres | ✅ RESPECTÉ — revert `git checkout 0dae11e` bit-exact, zéro recodage |
| Restaurer `template-parts/landing-offer.php` / `offers.css` | ✅ jamais touchés par v3.23 (audit `git diff`), donc déjà intacts |
| 3 cartes, disposition horizontale/grid desktop + mobile | ✅ mesuré (A1-A3, C1-C3, D1) |
| Scroll fluide vers le formulaire | ✅ mesuré après CHAQUE clic (B*.5, C5) |
| Contours de sélection + interactivité | ✅ mesuré (B*.2, A7, aria, récap) |
| Boutons de retour « ‹ رجوع » intacts | ✅ 86/86 back-link v3.24 + statique |
| Aucune autre partie de la landing touchée | ✅ 1697/1697 régressions vertes ; diff limité aux 6 fichiers listés §2 |
| Logo inchangé | ✅ jamais importé dans le diff ; 40px re-prouvé |
| Tunnel COD inchangé | ✅ champs/validation/idempotence/anti-flood/CAPI bit-exacts (62/62 tunnel, 30/30 REST) |

## 6. Déploiement

Commit sur `fix/restore-offers-grid` → merge `--no-ff` dans `main` → push
(déclenchement **WP Pusher** automatique) → vérification en ligne
(`verify_deploy_v325.py`) → `PROJECT_MAP.md` actualisé (§1/§2/§5-vicies-terdecies/historique 3.25).

*Action humaine recommandée* : vérifier l'onglet WP Pusher (pull main), purger
LiteSpeed/Cloudflare si cache CDN actif, puis parcours de contrôle : (a) LP →
section « عروض خاصة حصرية » avec les 3 cartes cliquables ; (b) `/produit-2/` et
`/produit-3/` → landings actives (fin des redirections 301) ; (c) commande test →
TYP avec les offres restantes en upsell.
