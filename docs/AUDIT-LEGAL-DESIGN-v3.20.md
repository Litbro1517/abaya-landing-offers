# AUDIT — PAGES RÉGLEMENTAIRES & D'INFORMATION (v3.20)

**Mandat 4P LP « /app protocole mandat 4P LP » — PHASE 1 : audit des pages réglementaires existantes.**
État de base : main @ fcf059f = v3.19 (synchronisée origin/main, arbre propre).
Références design : 3 screencaptures PDF fournies (catalogue-abayacollection-store —
conditions-generales / politique-de-confidentialite / mentions-legales, 2026-10-20),
utilisées comme **inspiration de mise en page uniquement** — aucun texte, aucun nom de
lien, aucune structure copiée (exigence explicite du mandat).

---

## 1. Inventaire et vérification stricte

### 1.1 Pages réglementaires et d'information (registre `inc-pages.php` → `abaya_pages()`)

Le thème enregistre **8 pages**, dont **7 pages réglementaires/d'information** + 1 page
transactionnelle (`merci`, hors périmètre). Noms, nombre, URLs et affichage footer :

| # | Slug (URL stable) | Titre H1 en base (actuel) | `<title>` navigateur | Lien footer |
|---|-------------------|---------------------------|----------------------|-------------|
| 1 | `about` → `/about/` | عن المتجر | عن المتجر \| Abaya Collection | ✅ oui |
| 2 | `modes-paiement` → `/modes-paiement/` | طرق الدفع | طرق الدفع \| Abaya Collection | ✅ oui |
| 3 | `livraison` → `/livraison/` | الشحن والتسليم 🚚 ⚠️ émoji | الشحن والتسليم \| Abaya Collection (propre) | ✅ oui |
| 4 | `conditions-retour` → `/conditions-retour/` | سياسة الاستبدال والاسترجاع 🔄 ⚠️ émoji | (propre) | ✅ oui |
| 5 | `politique-confidentialite` → `/politique-confidentialite/` | سياسة الخصوصية (الخاصة بنطاق Abaya Collection) 🔒 ⚠️ émoji | (propre) | ✅ oui |
| 6 | `conditions-utilisation` → `/conditions-utilisation/` | شروط الاستخدام 📜 ⚠️ émoji | (propre) | ✅ oui |
| 7 | `contact` → `/contact/` | اتصل بنا 💬 ⚠️ émoji | (propre) | ✅ oui |
| — | `merci` → `/merci/` (transactionnelle, **hors périmètre**) | شكراً لكِ! تم استلام طلبكِ | — | ❌ absent (voulu) |

**Constats d'intégrité :**
- Le H1 est servi par `page.php` via `the_title()` → le titre affiché provient de la
  **base de données** (semis initial), PAS du registre courant. Les 5 émojis des titres
  H1 sont donc à la fois dans le registre (`inc-pages.php`, colonne `[0]`) **et** en DB.
- Les `<title>` navigateur (colonne `[1]` du registre, filtre `pre_get_document_title`)
  et les meta descriptions (`[2]`) sont **déjà sans émoji** — aucun changement requis.
- Il n'existe **aucune page « Mentions Légales »** sur notre site : les 3 PDF fournis
  proviennent du site de référence (sœur) et servent d'inspiration design uniquement.
- URLs : structure `/`%postname%`/` forcée par `abaya_ensure_pages()` (permalink) —
  stables, non modifiées.

### 1.2 Petit footer en bas de page (`template-parts/legal-footer.php`)

Servi par `footer.php` pour toute page **non-landing**. Vérifié :

- **7 liens exactement**, dans l'ordre : `/about/` عن المتجر, `/modes-paiement/` طرق الدفع,
  `/livraison/` الشحن والتسليم, `/conditions-retour/` سياسة الاستبدال والاسترجاع,
  `/politique-confidentialite/` سياسة الخصوصية, `/conditions-utilisation/` شروط الاستخدام,
  `/contact/` اتصل بنا — + ligne copyright `© 2026 Abaya Collection. جميع الحقوق محفوظة.`
- **Zéro émoji** dans le footer. **Aucune modification** ne lui sera appliquée
  (liens, noms, nombre, agencement et présentation : 100 % intacts — exigence mandat).

### 1.3 En-tête légal (`template-parts/legal-header.php`)

Sans émoji. Porte le logo global centré (verrou v3.11) et le lien retour — **non modifié**.

---

## 2. Inventaire des émojis à supprimer (périmètre exclusif)

### 2.1 Contenus (`pages/*.html` — 15 émojis + 2 sélecteurs de variation U+FE0F)

| Fichier | Émojis (codepoints) | Position |
|---|---|---|
| `about.html` | 🌸 U+1F338, 💡 U+1F4A1, ✂️ U+2702+FE0F, 🧵 U+1F9F5, 📦 U+1F4E6, 💬 U+1F4AC, 🎯 U+1F3AF | 3 titres h2 + 4 puces li |
| `contact.html` | 📱 U+1F4F1, 📧 U+1F4E7, 📍 U+1F4CD, ⏰ U+23F0 | 4 puces li |
| `livraison.html` | ⏱️ U+23F1+FE0F, 📞 U+1F4DE | 1 h2 + 1 paragraphe |
| `modes-paiement.html` | 💵 U+1F4B5 | 1 titre h2 |
| `conditions-retour.html` | ⏰ U+23F0 | 1 titre h2 (« المهلة: ») |
| `conditions-utilisation.html` | — (propre) | — |
| `politique-confidentialite.html` | — (propre) | — |
| `merci.html` | — (propre, hors périmètre) | — |

### 2.2 Titres H1 (`inc-pages.php` — 5 émojis, repris en DB par le semis initial)

🚚 (`livraison`), 📜 (`conditions-utilisation`), 🔄 (`conditions-retour`),
🔒 (`politique-confidentialite`), 💬 (`contact`).

### 2.3 Émojis HORS périmètre (documentés, non touchés — règle de non-interférence)

- `template-parts/landing-offer.php` : ⌄ ★ ☆ ✓ — pictogrammes fonctionnels des LP
  (étoiles verrouillées v3.6/v3.16, garanties) — pages produit, PAS réglementaires.
- `template-parts/site-header.php` : ⑭ 🇦🇲 (LP vedette) — hors périmètre.
- `page-merci.php` : 🎁 ⑭ ✓ — page transactionnelle (🎁 déclaré légitime v3.17).

---

## 3. Écart design vs référence (3 PDF)

**Actuel (styles.css 622-680)** : fond crème plat, sections séparées par de simples
filets `border-top` (ou sans aucune délimitation pour `.policy-content`), texte gris
froid `#66665e`, puces de liste **par défaut du navigateur** (disque), H1 aligné par
défaut, aucune hiérarchie visuelle premium, aucun traitement des cartes.

**Référence (3 PDF — inspiration mise en page)** : fond crème chaud ; blocs de contenu
en **cartes arrondies** bordées d'un filet clair sur fond légèrement plus clair ;
**titre H1 centré** sobre ; listes à puces discrètes et régulières ; interlignage
confortable ; hiérarchie titres/corps nette ; rendu général épuré et professionnel.

**Traduction retenue (palette MARQUE imposée par le mandat — crème chaud + bordeaux/
chocolat, inspirée des PDF sans copier leurs contenus)** :

| Élément | Traitement v3.20 |
|---|---|
| Toile de page | `--paper` #F5F0E8 (crème chaud existant, conservé) |
| Sections | **Cartes** #FAF7F2, filet #E2D9CC, coins 14px, ombre chuchotée |
| H1 | **Bordeaux marque #6B1D2F**, centré + ornement or #C5A059 |
| Eyebrow « ABAYA COLLECTION » | or premium #C5A059, centré |
| H2 | **Chocolat #4A3222** + accent bordeaux #6B1D2F côté début (RTL) |
| Corps (p/li) | brun-gris chaud #6B5B4E (remplace le gris froid) |
| Puces d'énumération | points **or #C5A059** 6px réguliers (fin des disques navigateur) |
| `strong` | chocolat #4A3222 |
| Colonne de lecture | resserrée premium `min(880px)` centrée |
| Footer / header | **INTOUCHÉS** (protection stricte mandat) |

---

## 4. Provenance du contenu (DB) et stratégie de correction

- `abaya_ensure_pages()` (functions.php 1185-1232) **sème les pages UNE SEULE FOIS**
  (garde `get_option('abaya_pages_version')`, « ne jamais écraser une page existante »).
- Historique git : `pages/*.html` inchangés depuis le semis v2.0 (commit ce86781) →
  le contenu DB de production **est identique** aux fichiers du dépôt.
- **Conséquence** : nettoyer uniquement les fichiers du dépôt NE changerait RIEN en
  production. La suppression des émojis doit être **définitive** (mandat) ⇒ deux volets :
  1. **Sources nettoyées** (`pages/*.html` + `inc-pages.php`) — installations futures ;
  2. **Sync DB one-time** `abaya_strip_legal_page_emojis()` : suppression regex des
     seuls codepoints émojis (2300-23FF, 2600-27BF, 2B00-2BFF, 1F000-1FAFF, FE0F, ZWJ)
     sur `post_title` + `post_content` des 7 pages réglementaires — **rien d'autre** ;
     gated par option `abaya_legal_emoji_sync` = 'v1', hooks `init` + `admin_init`
     (exécution garantie dès le déploiement WP Pusher, front comme admin) ;
     protection anti-échec : un `preg_replace` null ne produit **jamais** d'écriture.
- Le strip DB est strictement équivalent à re-semer les fichiers nettoyés (preuve :
  § 4 ci-dessus) tout en **préservant** toute autre donnée éventuelle (0 risque).

---

## 5. Interdits vérifiés (respectés par le plan PHASE 2)

1. Aucun texte, paragraphe ou libellé modifié/reformulé/ajouté/supprimé **hors émojis**.
2. Liens hypertextes, noms de pages, nombre de pages, agencement du footer : intacts.
3. Structure textuelle 100 % identique (les `<section>/<h2>/<p>/<ul>/<li>` inchangés).
4. Hors périmètre absolu : landings, TYP, header, footer, REST, tunnel, tarifs.
