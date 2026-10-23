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
        /* v3.27 (mandat 4P LP « feat/chenille-webp-and-content ») : grille
         * ré-tariffée pour la collection hiver Chenille — prix unitaire 329 MAD
         * (tableau comparatif). Les 2/3 pièces prolongent les ÉCONOMIES DÉJÀ
         * AFFICHÉES (99/198 MAD) pour que chaque promesse « توفير » reste
         * arithmétiquement vraie sans inventer de nouveaux chiffres :
         *   2 pièces = 2×329 − 99 = 559 ; 3 pièces = 3×329 − 198 = 789.
         * Libellés et structure de la grille (3 cartes) STRICTEMENT INTACTS. */
        'bundles' => [1 => 329, 2 => 559, 3 => 789],
        'bundle_labels' => [
            1 => ['label' => 'قطعة واحدة', 'saving' => 'توفير 50 درهم'],
            2 => ['label' => 'قطعتان', 'saving' => 'توفير 99 درهم'],
            3 => ['label' => '3 قطع', 'saving' => 'توفير 198 درهم'],
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
        /* v3.27 : palette hiver Chenille (توب شونيل الراقي) — 6 couleurs du
         * mandat, images 1.webp…6.webp (teintes de pastilles échantillonnées
         * sur les visuels fournis). Ordre = ordre du mandat. */
        return [
            /* « label » = forme définie arabe (ال…) utilisée dans les textes d'alt/aria. */
            'أسود'       => ['hex' => '#1c1c20', 'label' => 'الأسود', 'image' => 'uploads/1.webp', 'thumb' => 'uploads/thumbs/1.webp'],
            'بوردو'      => ['hex' => '#431321', 'label' => 'البوردو', 'image' => 'uploads/2.webp', 'thumb' => 'uploads/thumbs/2.webp'],
            'بني'        => ['hex' => '#463739', 'label' => 'البني', 'image' => 'uploads/3.webp', 'thumb' => 'uploads/thumbs/3.webp'],
            'رمادي غامق' => ['hex' => '#53565f', 'label' => 'الرمادي الغامق', 'image' => 'uploads/4.webp', 'thumb' => 'uploads/thumbs/4.webp'],
            'بيج كاسيه'  => ['hex' => '#a79994', 'label' => 'البيج الكاسيه', 'image' => 'uploads/5.webp', 'thumb' => 'uploads/thumbs/5.webp'],
            'أخضر غامق'  => ['hex' => '#2e3b3a', 'label' => 'الأخضر الغامق', 'image' => 'uploads/6.webp', 'thumb' => 'uploads/thumbs/6.webp'],
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
        'bundles'       => $commerce['bundles'],
        'bundle_labels' => $commerce['bundle_labels'],
        'images' => [
            /* v3.27 : LCP = visuel noir de la collection Chenille (1.webp). */
            'lcp'        => 'uploads/1.webp',
            'lcp_color'  => 'أسود',
            'lcp_alt'    => 'العباية الشتوية باللون الأسود من توب شونيل الراقي',
            'colors'     => ['src' => 'uploads/Robe_comfy_Robe_chemise_avec_un_col_officier_et_deux_poche_tr_s_pratique_et_confortable_pour_tt___12_.webp', 'alt' => 'العباية باللون البيج بجودة عالية مناسبة لجميع الخرجات والعمل'],
            'specs'      => ['src' => 'uploads/visual-bordeaux.jpg', 'alt' => 'قصة العباية الواسعة وانسياب قماش الكريب باللون الأحمر الداكن'],
            'inspection' => ['src' => 'uploads/visual-blue-sky.jpg', 'alt' => 'إظهار ملمس قماش العباية بلون أزرق سماوي وجودة خياطتها'],
        ],
        'texts' => [
            'document_title'   => 'Abaya Collection',
            /* v3.27 : textes collection hiver Chenille (توب شونيل الراقي) —
             * modèle, tarifs (329 MAD) et caractéristiques col/manches/ceinture
             * selon le tableau comparatif du mandat. */
            'meta_description' => 'عباية شتوية من توب شونيل الراقي، دافئة وأنيقة. توصيل مجاني والدفع عند الاستلام في جميع مدن المغرب.',
            'headline'         => 'عباية شتوية من توب شونيل الراقي – دافئة وأنيقة',
            'slogan'           => 'اختيار يومي أنيق',
            'intro'            => 'عباية شتوية أنيقة ودافئة، بتوب شونيل راقٍ ملمسه ناعم وخياطة مضوبلة من الداخل والخارج، بجودة عالية لراحتك في الاستخدام اليومي.',
            'old_price'        => '379 درهم',
            'colors_bullets'   => [
                'عباية شتوية دافئة بجودة عالية مناسبة لجميع الخرجات والعمل',
                'متوفرة في 6 ألوان كي تختاري لونك المفضل بكل حرية.',
                'خياطة مزدوجة متينة|تضمن استدامة العباية وتألقها حتى مع الغسيل المتكرر والاستعمال اليومي.',
                'تخصيص القياسات|نظرًا لأننا نقوم بالخياطة المباشرة للموديل، يمكننا تعديل وتخصيص العباية من حيث (الطول والعرض/المقاس) حسب طولك ووزنك الخاص، مع الحفاظ التام على القصة والتصميم الأصلي.',
            ],
            'specs_title'      => 'معلومات إضافية وتفاصيل عن المنتج:',
            /* v3.27 : caractéristiques Chenille — tissu, col, manches,
             * ceinture (conformes aux visuels 1.webp…6.webp fournis). */
            'specs_items'      => [
                'نوع التوب: توب شونيل الراقي، ملمس ناعم ودافئ مناسب للموسم الشتوي.',
                'كول راقٍ مستدير مع بتنون أمامية بأزرار صدفية أنيقة.',
                'أكمام واسعة منفوخة بأساور مطاطية مريحة.',
                'حزام كوردون من نفس التوب يُربط عند الخصر لإبراز القامة.',
                'قصة انسيابية وفضفاضة متوفرة بمقاسات مختلفة باش تختاري المقاس المناسب ليك.',
            ],
            'inspection_title' => 'معاينة الطلبية والحق في الفحص',
            'inspection_lead'  => 'يمكنك فتح الطرد وفحص العباية قبل الدفع.',
            'inspection_note'  => 'يحق لك فتح الطرد وفحص القماش وجودة الخياطة أمام الموصل قبل إتمام الدفع.',
            /* v3.27 : nouvelle section « مراحل التصنيع » (5 étapes) — rendue
             * par le gabarit JUSTE APRÈS la section d'inspection et AVANT les 3
             * blocs de garanties. Les 5 étapes reprennent exclusivement des
             * affirmations déjà validées sur la page (خياطة مزدوجة، تخصيص
             * القياسات، خياطة من 5 إلى 6 أيام) — aucun gain nouveau inventé. */
            'manufacturing'    => [
                'title' => 'مراحل التصنيع',
                'steps' => [
                    ['اختيار التوب', 'نختار بعناية توب شونيل الراقي بجودة عالية، ملمس ناعم ودافئ مناسب لموسم الشتاء.'],
                    ['القص والتفصيل', 'يتم قص العباية حسب المقاسات المطلوبة مع الحفاظ التام على القصة والتصميم الأصلي.'],
                    ['الخياطة المزدوجة', 'خياطة مزدوجة متينة من الداخل والخارج تضمن استدامة العباية وتألقها حتى مع الغسيل المتكرر.'],
                    ['التفاصيل النهائية', 'تركيب الكول والأزرار، تجميع الأكمام المنفوخة بأساور مطاطية، وخياطة حزام الكوردون.'],
                    ['مراقبة الجودة والتغليف', 'فحص نهائي لجودة الثوب والخياطة قبل التغليف، وطلب خياطة المنتج يستغرق من 5 إلى 6 أيام.'],
                ],
            ],
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
