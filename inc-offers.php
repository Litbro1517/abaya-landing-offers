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
        'bundles' => [1 => 299, 2 => 499, 3 => 699],
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
        return [
            /* « label » = forme définie arabe (ال…) utilisée dans les textes d'alt/aria. */
            'أسود'       => ['hex' => '#292827', 'label' => 'الأسود', 'image' => 'uploads/frame_021.webp', 'thumb' => 'uploads/thumbs/frame_021.webp'],
            'بيج'        => ['hex' => '#d6c5a8', 'label' => 'البيج', 'image' => 'uploads/Robe_comfy_Robe_chemise_avec_un_col_officier_et_deux_poche_tr_s_pratique_et_confortable_pour_tt___12_.webp', 'thumb' => 'uploads/thumbs/Robe_comfy_Robe_chemise_avec_un_col_officier_et_deux_poche_tr_s_pratique_et_confortable_pour_tt___12_.webp'],
            'أبيض'       => ['hex' => '#f0eee7', 'label' => 'الأبيض', 'image' => 'uploads/frame_029-2.webp', 'thumb' => 'uploads/thumbs/frame_029-2.webp'],
            'أزرق داكن'  => ['hex' => '#26384d', 'label' => 'الأزرق الداكن', 'image' => 'uploads/swatch-blue-marine.jpg', 'thumb' => 'uploads/thumbs/swatch-blue-marine.jpg'],
            'أزرق سماوي' => ['hex' => '#9ec3d4', 'label' => 'الأزرق السماوي', 'image' => 'uploads/visual-blue-sky.jpg', 'thumb' => 'uploads/thumbs/visual-blue-sky.jpg'],
            'بني'        => ['hex' => '#75594e', 'label' => 'البني', 'image' => 'uploads/hero-moka.jpg', 'thumb' => 'uploads/thumbs/hero-moka.jpg'],
            'أحمر داكن'  => ['hex' => '#792f3c', 'label' => 'الأحمر الداكن', 'image' => 'uploads/visual-bordeaux.jpg', 'thumb' => 'uploads/thumbs/Robe_comfy_Robe_chemise_avec_un_col_officier_et_deux_poche_tr_s_pratique_et_confortable_pour_tt___6_.webp'],
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
            'lcp'        => 'uploads/hero-moka.jpg',
            'lcp_color'  => 'بني',
            'lcp_alt'    => 'العباية باللون البني الموكا',
            'colors'     => ['src' => 'uploads/Robe_comfy_Robe_chemise_avec_un_col_officier_et_deux_poche_tr_s_pratique_et_confortable_pour_tt___12_.webp', 'alt' => 'العباية باللون البيج بجودة عالية مناسبة لجميع الخرجات والعمل'],
            'specs'      => ['src' => 'uploads/visual-bordeaux.jpg', 'alt' => 'قصة العباية الواسعة وانسياب قماش الكريب باللون الأحمر الداكن'],
            'inspection' => ['src' => 'uploads/visual-blue-sky.jpg', 'alt' => 'إظهار ملمس قماش العباية بلون أزرق سماوي وجودة خياطتها'],
        ],
        'texts' => [
            'document_title'   => 'Abaya Collection',
            'meta_description' => 'عباية خريفية من توب لولان، أنيقة وانسيابية. توصيل مجاني والدفع عند الاستلام في جميع مدن المغرب.',
            'headline'         => 'عباية خريفية من توب لولان – أنيقة وانسيابية',
            'slogan'           => 'اختيار يومي أنيق',
            'intro'            => 'عباية خريفية أنيقة وانسيابية، بتوب مميز وفينيسيون نقية وخياطة مضوبلة من لداخل ومن برا، بجودة عالية لراحتك في الاستخدام اليومي.',
            'old_price'        => '349 درهم',
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
            'old_price'        => '349 درهم',
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

    /* ---------- Offre n°3 — landing secondaire /produit-2/ (angle confort) ---------- */
    'produit-2' => [
        'id'          => 'produit-2',
        'name'        => 'Produit 2',
        'product_id'  => (int) (defined('LANDING_PRODUCT_ID_2') ? LANDING_PRODUCT_ID_2 : $default_product),
        'sku'         => '',
        'layout'      => 'default',
        'assets_dir'  => 'uploads/offers/produit-2/',
        'colors'      => abaya_base_colors('uploads/offers/produit-2/'),
        'sizes'         => $commerce['sizes'],
        'bundles'       => $commerce['bundles'],
        'bundle_labels' => $commerce['bundle_labels'],
        'images' => [
            'lcp'        => 'uploads/offers/produit-2/hero.webp',
            'lcp_color'  => 'بيج',
            'lcp_alt'    => 'العباية باللون البيج بجودة عالية مناسبة لجميع الخرجات والعمل',
            'colors'     => ['src' => 'uploads/offers/produit-2/sec-colors.webp', 'alt' => 'العباية باللون الأبيض بخياطة متقنة وقصة مريحة'],
            'specs'      => ['src' => 'uploads/offers/produit-2/sec-specs.jpg', 'alt' => 'قصة العباية الواسعة وانسياب قماش الكريب باللون الأحمر الداكن'],
            'inspection' => ['src' => 'uploads/offers/produit-2/sec-inspection.jpg', 'alt' => 'إظهار ملمس قماش العباية بلون أزرق سماوي وجودة خياطتها'],
        ],
        'texts' => [
            'document_title'   => 'عباية لولان تجمع بين الراحة والأناقة',
            'meta_description' => 'عباية لولان ناعمة وانسيابية بخياطة متقنة لراحة يومية. توصيل مجاني والدفع عند الاستلام في جميع مدن المغرب.',
            'headline'         => 'عباية لولان تجمع بين الراحة والأناقة',
            'slogan'           => 'أناقة ترافق إطلالتك كل يوم',
            'intro'            => 'اختاري عباية لولان بإطلالة انسيابية وتفاصيل مدروسة تمنحك راحة كاملة من الصباح إلى المساء.',
            'old_price'        => '349 درهم',
            'colors_bullets'   => [
                'توب لولان ناعم يمنحك إحساساً مريحاً طوال اليوم',
                'متوفرة في 7 ألوان كي تختاري لونك المفضل بكل حرية.',
                'خياطة مزدوجة متينة|تصمد أمام الاستعمال اليومي والغسيل المتكرر دون أن تفقد أناقتها.',
                'تخصيص القياسات|نخيط الموديل خصيصاً لك، يمكننا تعديل الطول والعرض حسب طولك ووزنك مع الحفاظ على القصة الأصلية.',
            ],
            'specs_title'      => 'معلومات إضافية وتفاصيل عن المنتج:',
            'specs_items'      => [
                'نوع التوب: لولان ناعم مريح للاستعمال اليومي.',
                'قصة انسيابية فضفاضة تمنح حرية الحركة الكاملة.',
                'تفاصيل بسيطة وأنيقة تسهّل التنسيق مع مختلف الإطلالات.',
                'متوفرة بمقاسات مختلفة باش تختاري المقاس المناسب ليك.',
                'كول أوفيسيي مع جيوب جانبية مدفونة غير بارزة للحفاظ على جمالية الجوانب.',
            ],
            'inspection_title' => 'معاينة الطلبية والحق في الفحص',
            'inspection_lead'  => 'يمكنك فتح الطرد وفحص العباية قبل الدفع.',
            'inspection_note'  => 'يحق لك فتح الطرد وفحص القماش وجودة الخياطة أمام الموصل قبل إتمام الدفع.',
            'guide_title'      => 'كيف يمكنني تقديم الطلب؟',
            'guide_steps'      => [
                ['الخطوة الأولى: ملء استمارة الطلب', 'اختاري اللون والمقاس المناسبين، ثم قومي بملء معلوماتك الشخصية واضغطي على زر «اضغطي هنا للطلب».'],
                ['الخطوة الثانية: التأكيد الهاتفي', 'ستتلقين مكالمة من فريقنا لتأكيد المقاس، اللون، الكمية المطلوبة، وتأكيد عنوان التسليم.'],
                ['الخطوة الثالثة: الشحن والدفع عند الاستلام', 'سيتم شحن طلبيتك إلى باب منزلك، ويحق لك فحص العباية قبل تسليم المبلغ للموصل.'],
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

    /* ---------- Offre n°4 — landing secondaire /produit-3/ (angle élégance) ---------- */
    'produit-3' => [
        'id'          => 'produit-3',
        'name'        => 'Produit 3',
        'product_id'  => (int) (defined('LANDING_PRODUCT_ID_3') ? LANDING_PRODUCT_ID_3 : $default_product),
        'sku'         => '',
        'layout'      => 'default',
        'assets_dir'  => 'uploads/offers/produit-3/',
        'colors'      => abaya_base_colors('uploads/offers/produit-3/'),
        'sizes'         => $commerce['sizes'],
        'bundles'       => $commerce['bundles'],
        'bundle_labels' => $commerce['bundle_labels'],
        'images' => [
            'lcp'        => 'uploads/offers/produit-3/hero.jpg',
            'lcp_color'  => 'أحمر داكن',
            'lcp_alt'    => 'العباية باللون الأحمر الداكن بقصة واسعة مناسبة للخروج والعمل',
            'colors'     => ['src' => 'uploads/offers/produit-3/sec-colors.webp', 'alt' => 'العباية باللون الأسود بإطلالة راقية تناسب المناسبات'],
            'specs'      => ['src' => 'uploads/offers/produit-3/sec-specs.webp', 'alt' => 'العباية باللون الأبيض بتفاصيل بسيطة وأنيقة'],
            'inspection' => ['src' => 'uploads/offers/produit-3/sec-inspection.webp', 'alt' => 'العباية باللون البيج بجودة عالية مناسبة لجميع الخرجات والعمل'],
        ],
        'texts' => [
            'document_title'   => 'عباية لولان بلمسة راقية تناسبك',
            'meta_description' => 'عباية عملية بإطلالة راقية وألوان متعددة. توصيل مجاني والدفع عند الاستلام في جميع مدن المغرب.',
            'headline'         => 'عباية لولان بلمسة راقية تناسبك',
            'slogan'           => 'إطلالة مميزة بأسلوبك الخاص',
            'intro'            => 'عباية عملية بإطلالة راقية، اختاري اللون والمقاس المناسبين لكِ واستمتعي بأناقة بلا مجهود.',
            'old_price'        => '349 درهم',
            'colors_bullets'   => [
                'إطلالة راقية تناسب الخرجات والمناسبات والعمل',
                'متوفرة في 7 ألوان كي تختاري لونك المفضل بكل حرية.',
                'خياطة مزدوجة متينة|جودة تدوم مع الاستعمال المتكرر وتحافظ على تألق العباية.',
                'تخصيص القياسات|تعديل الطول والعرض حسب طولك ووزنك الخاص مع الحفاظ التام على القصة والتصميم.',
            ],
            'specs_title'      => 'معلومات إضافية وتفاصيل عن المنتج:',
            'specs_items'      => [
                'نوع التوب: لولان راقٍ بلمسة عملية.',
                'قصة واسعة وانسيابية تمنحك حضوراً أنيقاً في كل المناسبات.',
                'تصميم بلمسة راقية يسهّل التنسيق مع إطلالاتك المفضلة.',
                'متوفرة بمقاسات مختلفة باش تختاري المقاس المناسب ليك.',
                'كول أوفيسيي مع جيوب جانبية مدفونة مع الخياطة للحفاظ على جمالية الجوانب.',
            ],
            'inspection_title' => 'معاينة الطلبية والحق في الفحص',
            'inspection_lead'  => 'يمكنك فتح الطرد وفحص العباية قبل الدفع.',
            'inspection_note'  => 'يحق لك فتح الطرد وفحص القماش وجودة الخياطة أمام الموصل قبل إتمام الدفع.',
            'guide_title'      => 'كيف يمكنني تقديم الطلب؟',
            'guide_steps'      => [
                ['الخطوة الأولى: ملء استمارة الطلب', 'اختاري اللون والمقاس المناسبين، ثم قومي بملء معلوماتك الشخصية واضغطي على زر «اضغطي هنا للطلب».'],
                ['الخطوة الثانية: التأكيد الهاتفي', 'ستتلقين مكالمة من فريقنا لتأكيد المقاس، اللون، الكمية المطلوبة، وتأكيد عنوان التسليم.'],
                ['الخطوة الثالثة: الشحن والدفع عند الاستلام', 'سيتم شحن طلبيتك إلى باب منزلك، ويحق لك فحص العباية قبل تسليم المبلغ للموصل.'],
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

    ];

    return $offers;
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
