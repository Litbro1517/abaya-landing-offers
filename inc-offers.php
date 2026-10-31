<?php
if (!defined('ABSPATH')) exit;

/* =====================================================================
 * REGISTRE DES OFFRES (mandat multi-landing — Abaya Canvas)
 * ---------------------------------------------------------------------
 * Source unique de données : UNE offre = UNE entrée de données, PAS un
 * fichier PHP. Les landing pages secondaires sont servies par le gabarit
 * unique page-offer.php alimenté par ce registre (aucune duplication de
 * front-page.php).
 *
 * v3.26 (mandat 4P LP « protocole mandat 4P LP » — verrouillage de
 * l'architecture des pages) : le tunnel est FIGÉ à DEUX landings actives :
 * la vedette (racine) + UNE secondaire (produit-1). Les landings excédentaires
 * produit-2 et produit-3 (réintroduites à tort par le revert v3.25) sont
 * RETIRÉES du registre (liste abaya_retired_offers(), redirection 301 vers la
 * racine par abaya_gate_secondary_landing()) — la TYP ne propose plus qu'UN
 * SEUL produit d'upsell minimaliste (carte restante du registre après
 * exclusion des achats). DIFFÉRENCE MAJEURE avec v3.23 : la grille
 * commerciale des offres (bundles 1/2/3 pièces — 299/499/699 MAD) restaurée
 * puis validée en v3.25 est STABILISÉE et reste INTACTE.
 *
 * v3.27 (mandat 4P LP « feat/chenille-webp-and-content ») : la VEDETTE devient
 * la collection hiver Chenille (توب شونيل الراقي) — palette 6 couleurs
 * (1.webp…6.webp, LCP = 1.webp noir), textes/tarifs du tableau comparatif
 * (unitaire 329 MAD), nouvelle clé texts['manufacturing'] (5 étapes, gabarit
 * conditionnel). Grille commune ré-tariffée (329/559/789 — économies affichées
 * 50/99/198 conservées et vraies) ; structure 3 cartes, libellés, back-link,
 * logo, formulaire et architecture 2 landings STRICTEMENT INTACTS. *
 * v3.28 (mandat 4P LP « fix/main-lp-text-and-webp-injection ») : alignement
 * MOT-À-MOT de la vedette sur la matrice textuelle du mandat — titre
 * (« أنيقة وانسيابية »), grille 329/559/799 (économies 50/99/188 + prix de
 * référence 379/658/987 affichés par carte), les 8 détails produit exacts,
 * les 5 paragraphes « مراحل التصنيع » exacts (injection brute sans préfixe),
 * couleur « البلون كاسي » (terme du mandat) dans l'ordre d'énumération du
 * mandat, visuels secondaires alignés sur 1.webp…6.webp (5/2/4) + retrait
 * des anciennes images du socle. وصف المنتج (intro) CONSERVÉ tel quel.
 * Grille 3 cartes (structure), back-link ‹ رجوع, logo, formulaire,
 * produit-1 et architecture 2 landings STRICTEMENT INTACTS.
 *
 * v3.30 (mandat 4P LP « feat/wool-abaya-product-adaptation ») : transition
 * complète de la VEDETTE vers la collection « Abaya Laine » (عباية شتوية من
 * توب الصوف) — (a) grille tarifaire de la VEDETTE SEULE 479/799/1099 MAD
 * (économies 20/159/338 + الثمن المرجعي 499/958/1437 ; la grille commune
 * abaya_base_commerce() 329/559/799 reste INTACTE pour produit-1 — ZÉRO diff
 * de données hors vedette) ; (b) palette réduite à EXACTEMENT 2 couleurs du
 * mandat (البني الكريمي #b9a590, الأسود #1c1c1c) ; (c) 2 images PROVISOIRES
 * JPG (wool-1.jpg / wool-2.jpg + vignettes) REMPLACÉS en v3.34 par les
 * WebP DÉFINITIFS du mandat (wool-1..5.webp) ; (d) matrice textuelle mot-à-mot (titre, héros, 9
 * détails produit sans le sursaut الحزام, étapes 01/02 du guide) ;
 * (e) sélecteur multi-pièces (gabarit + app.js + REST additif) : compteurs
 * +/− par couleur pour les offres 2/3 pièces, tailles attribuées par pièce,
 * gating du bouton, données commandées portées par order_items/order_items_json.
 *
 * v3.32 (mandat 4P LP « feat/wool-abaya-product-adaptation » — ajustements
 * visuels VALIDÉS) : (a) DESIGN DES OFFRES = Option A de l'audit design
 * (AUDIT-OFFRES-v3.30.pdf) — ruban « الأكثر طلباً » bordeaux #6B1D2F, état
 * sélectionné en zone teintée émeraude 7 %, crochet 26px cerclé de blanc,
 * économies #2D6A70 (bloc v3.32 en fin de styles.css) ; (b) NETTOYAGE DES
 * PRIX — la mention « الثمن المرجعي » est SUPPRIMÉE de l'affichage et
 * remplacée par le prix d'origine BARRÉ en gris (#888, 0.85em, style inline
 * du mandat) sous le prix principal de chaque carte (donnée « ref » du
 * registre réutilisée : 499/958/1437) ; (c) SOUS-TITRE H1 — paragraphe en
 * arabe littéraire (Fusha) + phrase de finition isolée (clé « intro_finish »,
 * centrée/fine/italique) ; (d) LISTE FINALE des caractéristiques — 6 lignes
 * mot-à-mot. Grille 479/799/1099, 2 couleurs, 2 JPG, sélecteur multi-pièces
 * et DONNÉES produit-1 (bit-exactes, diff harnais 0 octet) : INCHANGÉS —
 * l'Option A est de la CHARTE unifiée de la section offres (v3.6 : copies
 * conformes) : ses sélecteurs :not(.is-upsell) s'appliquent aussi à produit-1.
 * Clés de chaque entrée :
 *   id            slug servie sur /{slug}/ (l'offre vedette est servie à la racine)
 *   name          nom éditorial (titre d'onglet secondaire, méta commande, cross-sell)
 *   product_id    produit WooCommerce (constante LANDING_PRODUCT_ID_N, repli historique)
 *   sku           repli éventuel (résolution produit par SKU si product_id vide)
 *   layout        gabarit du landing (toutes les offres partagent le même layout)
 *   assets_dir    dossier d'assets isolés ('' = uploads/ racine pour l'offre vedette,
 *                 inchangée bit-à-bit ; sinon uploads/offers/{slug}/)
 *   colors/sizes/bundles/bundle_labels  catalogue commercial de CETTE offre
 *   images        LCP + visuels de section (chemins relatifs au thème + alts)
 *   texts         textes (hero, sections, guide, FAQ) de CETTE offre
 *   cross_sell    slugs des offres à promouvoir sur /merci/ ([] = toutes les autres)
 *   upsell        réservé Phase 5 (route add-to-order) — null aujourd'hui
 * ===================================================================== */

/** Offre vedette servie à la racine (constante surchargeable dans wp-config.php). */
if (!defined('ABAYA_FRONT_OFFER')) define('ABAYA_FRONT_OFFER', 'accueil');

/** Grille commerciale commune (tailles, bundles) — un seul endroit, comme abaya_catalog(). */
function abaya_base_commerce() {
    return [
        'sizes'   => ['S', 'M', 'L', 'XL', 'XXL'],
        /* Grille tarifaire AUTORITAIRE des offres (MAD) — le serveur est la seule
         * autorité sur le prix d'une commande, le front n'est qu'un affichage. */
        /* v3.28 (mandat 4P LP « fix/main-lp-text-and-webp-injection ») : grille
         * du mandat MOT-À-MOT — 329/559/799 MAD, économies 50/99/188 درهم,
         * prix de référence affiché par carte (« الثمن المرجعي ») 379/658/987 :
         * chaque « توفير » reste arithmétiquement VRAI (379−329=50 ;
         * 658−559=99 ; 987−799=188). Structure 3 cartes et libellés
         * (قطعة واحدة/قطعتان/3 قطع) STRICTEMENT INTACTS. */
        'bundles' => [1 => 329, 2 => 559, 3 => 799],
        'bundle_labels' => [
            1 => ['label' => 'قطعة واحدة', 'saving' => 'توفير 50 درهم', 'ref' => 379],
            2 => ['label' => 'قطعتان', 'saving' => 'توفير 99 درهم', 'ref' => 658],
            3 => ['label' => '3 قطع', 'saving' => 'توفير 188 درهم', 'ref' => 987],
        ],
    ];
}

/**
 * Couleurs d'une offre.
 * $assets_dir === '' : définition HISTORIQUE de l'offre vedette (chemins uploads/
 * racine exacts du socle — rendu racine inchangé bit-à-bit).
 * Sinon : copies isolées dans uploads/offers/{slug}/ (personnalisation indépendante).
 */
function abaya_base_colors($assets_dir) {
    if ($assets_dir === '') {
        /* v3.34 (mandat 4P LP) : les WebP DÉFINITIFS du mandat remplacent les
         * JPG PROVISOIRES v3.30 (fin du signalement « remplacement ultérieur »).
         * Palette INCHANGÉE — toujours EXACTEMENT les 2 couleurs (« دائرتان فقط »)
         * dans l'ordre de l'énumération : البني الكريمي (#b9a590) puis الأسود
         * (#1c1c1c). Mapping des fichiers (copies BIT-EXACTES, natif 1080×1350,
         * zéro réencodage) : Hero principal/LCP = « 1 (6).webp » → wool-1.webp ;
         * Hero 2ᵉ image / variante de couleur = « 2 (3).webp » → wool-2.webp ;
         * sections de contenu : « 8.webp » → wool-3.webp (manches),
         * « 7.webp » → wool-4.webp, « 6 (1).webp » → wool-5.webp.
         * Vignettes galerie régénérées 151×170 (format du système). */
        return [
            /* « label » = forme définie arabe (ال…) utilisée dans les textes d'alt/aria. */
            'بني كريمي'  => ['hex' => '#b9a590', 'label' => 'البني الكريمي', 'image' => 'uploads/wool-1.webp', 'thumb' => 'uploads/thumbs/wool-1.webp'],
            'أسود'       => ['hex' => '#1c1c1c', 'label' => 'الأسود', 'image' => 'uploads/wool-2.webp', 'thumb' => 'uploads/thumbs/wool-2.webp'],
        ];
    }
    return [
        'أسود'       => ['hex' => '#292827', 'label' => 'الأسود', 'image' => $assets_dir . 'noir.webp', 'thumb' => $assets_dir . 'thumbs/noir.webp'],
        'بيج'        => ['hex' => '#d6c5a8', 'label' => 'البيج', 'image' => $assets_dir . 'beige.webp', 'thumb' => $assets_dir . 'thumbs/beige.webp'],
        'أبيض'       => ['hex' => '#f0eee7', 'label' => 'الأبيض', 'image' => $assets_dir . 'blanc.webp', 'thumb' => $assets_dir . 'thumbs/blanc.webp'],
        'أزرق داكن'  => ['hex' => '#26384d', 'label' => 'الأزرق الداكن', 'image' => $assets_dir . 'bleu-marine.webp', 'thumb' => $assets_dir . 'thumbs/bleu-marine.webp'],
        'أزرق سماوي' => ['hex' => '#9ec3d4', 'label' => 'الأزرق السماوي', 'image' => $assets_dir . 'bleu-ciel.webp', 'thumb' => $assets_dir . 'thumbs/bleu-ciel.webp'],
        'بني'        => ['hex' => '#75594e', 'label' => 'البني', 'image' => $assets_dir . 'marron.webp', 'thumb' => $assets_dir . 'thumbs/marron.webp'],
        'أحمر داكن'  => ['hex' => '#792f3c', 'label' => 'الأحمر الداكن', 'image' => $assets_dir . 'bordeaux.webp', 'thumb' => $assets_dir . 'thumbs/bordeaux.webp'],
    ];
}

/** Configuration éditoriale et commerciale isolée de chaque landing. */
function abaya_offers() {
    $commerce    = abaya_base_commerce();
    $front_offer = defined('ABAYA_FRONT_OFFER') ? ABAYA_FRONT_OFFER : 'accueil';
    $default_product = (int) (defined('LANDING_PRODUCT_ID') ? LANDING_PRODUCT_ID : 15);

    $offers = [

    /* ---------- Offre n°1 — VENDETTE servie à la racine (inchangée bit-à-bit) ---------- */
    $front_offer => [
        'id'          => $front_offer,
        'name'        => 'Abaya Collection',
        'product_id'  => $default_product,
        'sku'         => '',
        'layout'      => 'default',
        'assets_dir'  => '',
        'colors'      => abaya_base_colors(''),
        'sizes'         => $commerce['sizes'],
        /* v3.30 (mandat 4P LP « feat/wool-abaya-product-adaptation ») : grille
         * de la VEDETTE SEULE — 479/799/1099 MAD (libellés ×3 de la charte
         * CONSERVÉS) + économies 20/159/338 درهم + الثمن المرجعي 499/958/1437
         * affiché par carte. Chaque توفير reste arithmétiquement VRAI :
         * 499−479=20 ; 958−799=159 ; 1437−1099=338. La grille commune
         * abaya_base_commerce() (329/559/799) reste intacte pour produit-1. */
        /* v3.35 (mandat 4P LP « feat/wool-abaya-product-adaptation ») : PRIX
         * HERO + OFFRE 1 PIÈCE — prix barré/référence 499 → 529 درهم et
         * économie توفير 20 → توفير 50 درهم (529−479=50 : arithmétiquement
         * VRAI, affiché au Hero [del + pill] ET carte 1 [del + saving]).
         * Le PRIX PRINCIPAL 479 ainsi que les offres 2/3 pièces (799/1099,
         * barrés 958/1437, توفير 159/338) restent STRICTEMENT INTACTS. */
        'bundles'       => [1 => 479, 2 => 799, 3 => 1099],
        'bundle_labels' => [
            1 => ['label' => 'قطعة واحدة', 'saving' => 'توفير 50 درهم', 'ref' => 529],
            2 => ['label' => 'قطعتان', 'saving' => 'توفير 159 درهم', 'ref' => 958],
            3 => ['label' => '3 قطع', 'saving' => 'توفير 338 درهم', 'ref' => 1437],
        ],
        'images' => [
            /* v3.34 (mandat 4P LP) : IMAGES WEBP DÉFINITIVES — fin des JPG
             * provisoires v3.30. Hero (Sélection des couleurs) : image
             * principale = « 1 (6).webp » → wool-1.webp (LCP) ; seconde image
             * associée au Hero / variante de couleur = « 2 (3).webp » →
             * wool-2.webp. Sections de contenu PAR ORDRE DE PRIORITÉ du
             * mandat : 1ʳᵉ = « 8.webp » → wool-3.webp (met en valeur les
             * manches) ; 2ᵉ = « 7.webp » → wool-4.webp ; 3ᵉ = « 6 (1).webp »
             * → wool-5.webp. Copies BIT-EXACTES (natif 1080×1350, zéro
             * réencodage/déformation) — cadres CSS (aspect-ratio + object-fit)
             * INCHANGÉS ; les alts ne mentionnent plus de couleur précise
             * (hors LCP, rattaché à la teinte بني كريمي du sélecteur). */
            'lcp'        => 'uploads/wool-1.webp',
            'lcp_color'  => 'بني كريمي',
            'lcp_alt'    => 'العباية الشتوية باللون البني الكريمي من توب الصوف',
            'colors'     => ['src' => 'uploads/wool-3.webp', 'alt' => 'العباية الشتوية من توب الصوف — إبراز الأكمام الواسعة'],
            'specs'      => ['src' => 'uploads/wool-4.webp', 'alt' => 'قصة العباية الواسعة وانسياب توب الصوف'],
            'inspection' => ['src' => 'uploads/wool-5.webp', 'alt' => 'معاينة العباية والتأكد من ملمس توب الصوف'],
        ],
        'texts' => [
            'document_title'   => 'Abaya Collection',
            /* v3.30 (mandat « feat/wool-abaya-product-adaptation ») : textes de
             * la collection « Abaya Laine » — titre + description héros MOT-À-MOT
             * du mandat ; bullet couleurs = phrase exacte du mandat (2 teintes) ;
             * bullets 1/3/4 (qualité, خياطة مزدوجة, تخصيص القياسات) conservés —
             * toujours vrais pour la nouvelle collection. */
            'meta_description' => 'عباية شتوية من توب الصوف، دافئة وأنيقة. توصيل مجاني والدفع عند الاستلام في جميع مدن المغرب.',
            'headline'         => 'عباية شتوية من توب الصوف – انسيابية ودوبل كلوش',
            'slogan'           => 'اختيار يومي أنيق',
            /* v3.33 (mandat 4P LP) : SOUS-TITRE H1 — CORRECTION DE MISE EN
             * FORME EXCLUSIVE (prix/offres/sélecteur déjà validés, ZÉRO autre
             * changement) : bloc HOMOGÈNE — la phrase en arabe littéraire
             * (texte EXACT de la PARTIE 3 du mandat) est suivie
             * IMMÉDIATEMENT, dans le MÊME flux et à la MÊME police/taille que
             * le corps, par la phrase Darija ENCADRÉE DE DEUX TIRETS et
             * mise en ITALIQUE (seule partie italique — rendue par
             * <em class="product-intro-darija"> dans landing-offer.php ;
             * les tirets encadrent la phrase, la clé « intro_finish » est
             * conservée telle quelle : produit-1 reste bit-exact). */
            /* v3.35 (mandat 4P LP « feat/wool-abaya-product-adaptation ») :
             * SOUS-TITRE H1 — TEXTE HOMOGÈNE VALIDÉ (remplace la matrice
             * v3.33 « Fusha + finition Darija encadrée de tirets/italique ») :
             * la description devient UNE SEULE phrase — la finition est FONDUE
             * dans le flux (« …المظهر الأنيق، بفينيسيون نقية وخياطة متقونة
             * من الداخل ومن برا. ») ; « intro_finish » est vidée → la branche
             * conditionnelle <em> du gabarit ne rend plus RIEN (mécanisme
             * conservé pour produit-1, sans la clé : bit-exact). */
            'intro'            => 'عباية شتوية من توب الصوف، بقصة دوبل كلوش واسعة وفضفاضة وانسيابية، تجمع بين الدفء والاحتشام والمظهر الأنيق، بفينيسيون نقية وخياطة متقونة من الداخل ومن برا.',
            'intro_finish'     => '',
            'old_price'        => '529 درهم',
            'colors_bullets'   => [
                'عباية شتوية دافئة بجودة عالية مناسبة لجميع الخرجات والعمل',
                'متوفرة بلونين: البني الكريمي والأسود.',
                'خياطة مزدوجة متينة|تضمن استدامة العباية وتألقها حتى مع الغسيل المتكرر والاستعمال اليومي.',
                'تخصيص القياسات|نظرًا لأننا نقوم بالخياطة المباشرة للموديل، يمكننا تعديل وتخصيص العباية من حيث (الطول والعرض/المقاس) حسب طولك ووزنك الخاص، مع الحفاظ التام على القصة والتصميم الأصلي.',
            ],
            'specs_title'      => 'معلومات إضافية وتفاصيل عن المنتج:',
            /* v3.32 (mandat 4P LP « feat/wool-abaya-product-adaptation ») :
             * LISTE FINALE ET ÉPURÉE des caractéristiques — EXACTEMENT les 6
             * lignes de la matrice du mandat, injectées MOT-À-MOT (ponctuation
             * incluse). Les anciennes lignes الستايل والديكور et
             * الخياطة والفينيسيون disparaissent (la phrase de finition migre
             * vers le sous-titre H1 en « intro_finish ») ; الفتحة et الكول
             * sont fusionnées dans la ligne unique « الفتحة والكول ». */
            'specs_items'      => [
                'نوع التوب: توب صوف دافئ وكثيف، مريح ومناسب للموسم الشتوي بمظهر وألوان هادئة وجذابة.',
                'الانسدال والجودة: الثوب طايح وما كيلصقش بالجسم، ما كيبينش تفاصيل الجسم، كيعطيك لوك أنيق ومميز.',
                'قصة العباية: قصة واسعة وفضفاضة على شكل دوبل كلوش، كتجمع بين الاحتشام والمظهر الأنيق وحرية الحركة.',
                'الفتحة والكول: العباية مصممة بكول واسع وفتحة أمامية على شكل V وبزر في الأعلى، كيسهل الارتداء بلا مشقة، وتقدري تلبسيها مقفولة أو مفتوحة على حسب ذوقك.',
                'الأكمام: أكمام واسعة بستايل الإيفازي والريفير، مطوية ومثبتة، كتعطي مظهر أنيق وعصري للعباية.',
                'الجيوب: جيوب أمامية كبيرة تعطي ستايل مميز للعباية.',
            ],
            'inspection_title' => 'معاينة الطلبية والحق في الفحص',
            'inspection_lead'  => 'يمكنك فتح الطرد وفحص العباية قبل الدفع.',
            'inspection_note'  => 'يحق لك فتح الطرد وفحص القماش وجودة الخياطة أمام الموصل قبل إتمام الدفع.',
            /* v3.29 (mandat « fix/main-lp-manufacturing-and-contrast ») : la
             * section passe à EXACTEMENT 4 blocs d'étapes dotés de leurs titres
             * respectifs (couples [titre, texte] — audit designer v3.29 §1.2 :
             * correspondance titre/paragraphe un pour un, aucun remaniement de
             * contenu). Les textes restent INCHANGÉS mot-à-mot (v3.28). Le 5e
             * paragraphe — déjà une clôture, pas une étape — est extrait en clé
             * « note » et rendu par le gabarit en bandeau .inspection-banner. */
            'manufacturing'    => [
                'title' => 'مراحل التصنيع',
                'steps' => [
                    ['اختيار الموديل والتوب', 'كنختارو موديل مميز، وكنحرصو على اختيار توب بجودة عالية، حيت جودة الثوب هي أولوية عندنا.'],
                    ['اختبار التوب والجودة', 'كنخضعو التوب لاختبار عملي باش نتأكدو من الجودة ديالو، وأنه ما كيشربش وما كيتبدلش الشكل ديالو ولا المقاسات ديالو من بعد الغسيل.'],
                    ['الفصالة والخياطة', 'كنهتمو بالفصالة والخياطة، باش كل تفصيلة فالعباية ديالك تكون مضبوطة ومخدومة مزيان.'],
                    ['مراقبة الجودة والمتانة', 'من بعد الخياطة، كنخضعو العباية لاختبار الجودة والمتانة، وكنراجعو الخياطة والتفاصيل من الداخل ومن برا، باش نتأكدو من جودة الخدمة قبل ما نرسلوها للزبونة.'],
                ],
                'note' => 'هاد الشي كلو باش توصلك عباية مخدومة بعناية، بخياطة متقونة وجودة عالية.',
            ],
            'guide_title'      => 'كيف يمكنني تقديم الطلب؟',
            /* v3.30 : textes des étapes 01 et 02 du mandat MOT-À-MOT (sélection
             * de l'offre + couleur/moule par pièce ; confirmation téléphonique
             * élargie à l'offre et au couple couleur/moule de CHAQUE pièce).
             * Étape 03 : SANS CHANGEMENT (consigne explicite). */
            'guide_steps'      => [
                ['الخطوة الأولى: ملء استمارة الطلب', 'اختاري العرض المناسب (قطعة واحدة أو قطعتان أو 3 قطع)، ثم حددي لون ومقاس كل قطعة، ثم قومي بملء معلوماتك الشخصية (الاسم الكامل، رقم الهاتف، المدينة، والعنوان) واضغطي على زر «اضغطي هنا للطلب».'],
                ['الخطوة الثانية: التأكيد الهاتفي', 'بعد وصولك إلى صفحة الشكر والتأكد من صحة الاختيارات، ستتلقين مكالمة هاتفية من فريقنا لتأكيد العرض المختار، واللون والمقاس لكل قطعة، وتأكيد عنوان التسليم.'],
                ['الخطوة الثالثة: الشحن والدفع عند الاستلام', 'سيتم شحن طلبيتك مباشرة إلى باب منزلك. يحق لك فتح الطرد ومعاينة العباية والتأكد من جودة الثوب والخياطة قبل تسليم المبلغ للموصل.'],
            ],
            'trust'            => [
                ['الدفع عند الاستلام', 'تأكدي من الطلب قبل الدفع'],
                ['توصيل سريع', 'التوصيل خلال 24 إلى 72 ساعة هي المدة المعتمدة عموماً. طلب خياطة المنتج يستغرق من 5 إلى 6 أيام.'],
                ['استبدال المقاس', 'يمكن استبدال المقاس حسب رغبتك، بشرط تحمل مصاريف التوصيل فقط.'],
            ],
            'faq'              => [
                ['شحال المدة باش توصل الطلبية؟', 'التوصيل خلال 24 إلى 72 ساعة هي المدة المعتمدة عموماً، أما طلب خياطة المنتج يستغرق من 5 إلى 6 أيام.'],
                ['شحال ثمن التوصيل ؟', 'التوصيل فابور على جميع المنتجات ديالنا.'],
                ['واش إيلا لقيت فالطلبية شي مشكل نقدر نرجع فلوسي ؟', 'يحق لك تقديم طلب الاستبدال أو الإرجاع خلال 72 ساعة من استلام الطرد وفق الشروط الموضحة في سياسة الاستبدال والاسترجاع.'],
                ['واش نقدر نبدل القياس؟', 'نعم، كنديرو خدمة تغيير القياس، يمكن لك تبدل القياس حسب رغبتك بشرط تخلص مصاريف التوصيل فقط.'],
            ],
            'contact_heading'  => 'من أجل أي استفسار، تواصلوا معنا عبر الواتساب:',
        ],
        'cross_sell' => [],
        'upsell'     => null,
    ],

    /* ---------- Offre n°2 — landing secondaire /produit-1/ ---------- */
    'produit-1' => [
        'id'          => 'produit-1',
        'name'        => 'Produit 1',
        'product_id'  => (int) (defined('LANDING_PRODUCT_ID_1') ? LANDING_PRODUCT_ID_1 : $default_product),
        'sku'         => '',
        'layout'      => 'default',
        'assets_dir'  => 'uploads/offers/produit-1/',
        'colors'      => abaya_base_colors('uploads/offers/produit-1/'),
        'sizes'         => $commerce['sizes'],
        'bundles'       => $commerce['bundles'],
        'bundle_labels' => $commerce['bundle_labels'],
        'images' => [
            'lcp'        => 'uploads/offers/produit-1/hero.jpg',
            'lcp_color'  => 'بني',
            'lcp_alt'    => 'العباية باللون البني الموكا',
            'colors'     => ['src' => 'uploads/offers/produit-1/sec-colors.webp', 'alt' => 'العباية باللون البيج بجودة عالية مناسبة لجميع الخرجات والعمل'],
            'specs'      => ['src' => 'uploads/offers/produit-1/sec-specs.jpg', 'alt' => 'قصة العباية الواسعة وانسياب قماش الكريب باللون الأحمر الداكن'],
            'inspection' => ['src' => 'uploads/offers/produit-1/sec-inspection.jpg', 'alt' => 'إظهار ملمس قماش العباية بلون أزرق سماوي وجودة خياطتها'],
        ],
        'texts' => [
            'document_title'   => 'عباية خريفية من توب لولان – أناقة وانسيابية',
            'meta_description' => 'عباية لولان أنيقة وانسيابية للاستخدام اليومي. توصيل مجاني والدفع عند الاستلام في جميع مدن المغرب.',
            'headline'         => 'عباية خريفية من توب لولان – أناقة وانسيابية',
            'slogan'           => 'اختيار يومي أنيق',
            'intro'            => 'عباية خريفية أنيقة وانسيابية، بتوب مميز وخياطة متقنة لراحة يومية.',
            'old_price'        => '379 درهم',
            'colors_bullets'   => [
                'عباية ذات جودة عالية مناسبة لجميع الخرجات والعمل',
                'متوفرة في 7 ألوان كي تختاري لونك المفضل بكل حرية.',
                'خياطة مزدوجة متينة|تضمن استدامة العباية وتألقها حتى مع الغسيل المتكرر والاستعمال اليومي.',
                'تخصيص القياسات|نظرًا لأننا نقوم بالخياطة المباشرة للموديل، يمكننا تعديل وتخصيص العباية من حيث (الطول والعرض/المقاس) حسب طولك ووزنك الخاص، مع الحفاظ التام على القصة والتصميم الأصلي.',
            ],
            'specs_title'      => 'معلومات إضافية وتفاصيل عن المنتج:',
            'specs_items'      => [
                'نوع التوب: توب لولان مناسب للموسم الخريفي.',
                'قصة العباية انسيابية وفضفاضة باش تعطيك راحة فالحركة واللبس.',
                'العباية مصممة بتفاصيل بسيطة وأنيقة كتخليها سهلة التنسيق مع مختلف الإطلالات.',
                'متوفرة بمقاسات مختلفة باش تختاري المقاس المناسب ليك.',
                'العباية مصممة بكول أوفيسيي مع جيوب جانبية مدفونة مع الخياطة، غير بارزة للحفاظ على جمالية وأناقة الجوانب.',
            ],
            'inspection_title' => 'معاينة الطلبية والحق في الفحص',
            'inspection_lead'  => 'يمكنك فتح الطرد وفحص العباية قبل الدفع.',
            'inspection_note'  => 'يحق لك فتح الطرد وفحص القماش وجودة الخياطة أمام الموصل قبل إتمام الدفع.',
            'guide_title'      => 'كيف يمكنني تقديم الطلب؟',
            'guide_steps'      => [
                ['الخطوة الأولى: ملء استمارة الطلب', 'اختاري اللون والمقاس المناسبين، ثم قومي بملء معلوماتك الشخصية (الاسم الكامل، رقم الهاتف، المدينة، والعنوان) واضغطي على زر «اضغطي هنا للطلب».'],
                ['الخطوة الثانية: التأكيد الهاتفي', 'بعد وصولك إلى صفحة الشكر والتأكد من صحة الاختيارات، ستتلقين مكالمة هاتفية من فريقنا لتأكيد المقاس، اللون، الكمية المطلوبة، وتأكيد عنوان التسليم.'],
                ['الخطوة الثالثة: الشحن والدفع عند الاستلام', 'سيتم شحن طلبيتك مباشرة إلى باب منزلك. يحق لك فتح الطرد ومعاينة العباية والتأكد من جودة الثوب والخياطة قبل تسليم المبلغ للموصل.'],
            ],
            'trust'            => [
                ['الدفع عند الاستلام', 'تأكدي من الطلب قبل الدفع'],
                ['توصيل سريع', 'التوصيل خلال 24 إلى 72 ساعة هي المدة المعتمدة عموماً. طلب خياطة المنتج يستغرق من 5 إلى 6 أيام.'],
                ['استبدال المقاس', 'يمكن استبدال المقاس حسب رغبتك، بشرط تحمل مصاريف التوصيل فقط.'],
            ],
            'faq'              => [
                ['شحال المدة باش توصل الطلبية؟', 'التوصيل خلال 24 إلى 72 ساعة هي المدة المعتمدة عموماً، أما طلب خياطة المنتج يستغرق من 5 إلى 6 أيام.'],
                ['شحال ثمن التوصيل ؟', 'التوصيل فابور على جميع المنتجات ديالنا.'],
                ['واش إيلا لقيت فالطلبية شي مشكل نقدر نرجع فلوسي ؟', 'يحق لك تقديم طلب الاستبدال أو الإرجاع خلال 72 ساعة من استلام الطرد وفق الشروط الموضحة في سياسة الاستبدال والاسترجاع.'],
                ['واش نقدر نبدل القياس؟', 'نعم، كنديرو خدمة تغيير القياس، يمكن لك تبدل القياس حسب رغبتك بشرط تخلص مصاريف التوصيل فقط.'],
            ],
            'contact_heading'  => 'من أجل أي استفسار، تواصلوا معنا عبر الواتساب:',
        ],
        'cross_sell' => [],
        'upsell'     => null,
    ],

    /* v3.26 : les entrées 'produit-2' et 'produit-3' (landings secondaires
     * excédentaires réintroduites à tort par le revert v3.25) sont RETIRÉES
     * du registre — voir abaya_retired_offers() + le gate 301 de
     * abaya_gate_secondary_landing() (redirection permanente vers la racine).
     * La grille commerciale commune (bundles 1/2/3 pièces) reste INTACTE. */
    ];

    return $offers;
}

/* v3.26 : slugs RETIRÉS du registre (landings secondaires excédentaires —
 * architecture verrouillée à DEUX landings actives : la vedette (racine) +
 * produit-1). Les pages WordPress correspondantes peuvent subsister en base
 * (semis historique « ne jamais écraser ») : abaya_gate_secondary_landing()
 * les redirige en 301 vers la racine — aucun rendu de page vide, les anciens
 * liens publicitaires restent servis par une redirection permanente. */
function abaya_retired_offers() {
    return ['produit-2', 'produit-3'];
}

/** Identifiant de l'offre courante : global (gabarits) → page courante (slug) → offre vedette. */
function abaya_current_offer_id() {
    if (!empty($GLOBALS['abaya_offer_id'])) return sanitize_key($GLOBALS['abaya_offer_id']);
    if (is_page()) {
        $slug = (string) get_post_field('post_name', get_queried_object_id());
        if (isset(abaya_offers()[$slug])) return $slug;
    }
    return defined('ABAYA_FRONT_OFFER') ? ABAYA_FRONT_OFFER : 'accueil';
}

/** Configuration complète d'une offre (null si inconnue). */
function abaya_offer_config($offer_id = null) {
    $offers   = abaya_offers();
    $offer_id = $offer_id ? sanitize_key($offer_id) : abaya_current_offer_id();
    return isset($offers[$offer_id]) ? $offers[$offer_id] : null;
}

/** L'offre courante est-elle la vedette de la racine ? */
function abaya_is_front_offer($offer_id = null) {
    $front = defined('ABAYA_FRONT_OFFER') ? ABAYA_FRONT_OFFER : 'accueil';
    return ($offer_id ? sanitize_key($offer_id) : abaya_current_offer_id()) === $front;
}

/** Sommes-nous sur une landing (racine ou offre secondaire) ? Remplace is_front_page() partout. */
function abaya_is_landing_page() {
    return is_front_page() || (is_page() && isset(abaya_offers()[(string) get_post_field('post_name', get_queried_object_id())]));
}
