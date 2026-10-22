<?php
if (!defined('ABSPATH')) exit;

require_once get_template_directory() . '/inc-pages.php';
require_once get_template_directory() . '/inc-offers.php'; // registre des offres (mandat multi-landing)

define('ABAYA_PAGES_VERSION', '2.0');

/* ---------- Suivi Meta (Pixel + CAPI) ----------
 * Les identifiants se définissent dans le wp-config.php de l'hébergement :
 *   define('LANDING_PRODUCT_ID', 15);
 *   define('META_PIXEL_ID', 'VOTRE_PIXEL_ID');
 *   define('META_CAPI_TOKEN', 'VOTRE_CAPI_TOKEN');
 *   define('META_TEST_EVENT_CODE', 'TESTxxxx');
 * Valeurs par défaut vides ci-dessous : le thème reste fonctionnel même si le
 * bloc n'a pas encore été inséré dans wp-config.php (suivi simplement inactif).
 */
if (!defined('META_PIXEL_ID'))      define('META_PIXEL_ID', '');
if (!defined('META_CAPI_TOKEN'))    define('META_CAPI_TOKEN', '');
if (!defined('META_TEST_EVENT_CODE')) define('META_TEST_EVENT_CODE', '');
if (!defined('LANDING_PRODUCT_ID')) define('LANDING_PRODUCT_ID', 15); // produit réel du landing

add_theme_support('title-tag');
add_theme_support('woocommerce');

/* ---------- Titres et descriptions (repris de vos fichiers HTML d'origine) ---------- */
add_filter('pre_get_document_title', function ($title) {
    if (abaya_is_landing_page()) {
        $abaya_offer = abaya_offer_config();
        return $abaya_offer ? (string) $abaya_offer['texts']['document_title'] : 'Abaya Collection';
    }
    if (is_page()) {
        $pages = abaya_pages();
        $slug  = get_post_field('post_name', get_queried_object_id());
        if (isset($pages[$slug])) return $pages[$slug][1];
    }
    return $title;
});

function abaya_meta_description() {
    if (abaya_is_landing_page()) {
        $abaya_offer = abaya_offer_config();
        return $abaya_offer ? (string) $abaya_offer['texts']['meta_description'] : '';
    }
    if (is_page()) {
        $pages = abaya_pages();
        $slug  = get_post_field('post_name', get_queried_object_id());
        if (isset($pages[$slug])) return $pages[$slug][2];
    }
    return '';
}

/* ---------- noindex sur la page de remerciement ---------- */
add_filter('wp_robots', function ($robots) {
    if (is_page('merci')) $robots['noindex'] = true;
    return $robots;
});

/* ---------- Page /merci/ : jamais de cache (données personnelles, tunnel checkout) ----------
 * Les en-têtes no-cache doivent partir AVANT tout rendu : hook template_redirect.
 * Le hook LiteSpeed force le plugin à servir la page en no-cache ; sinon la page
 * dynamique risquerait d'être servie depuis le cache LiteSpeed/CDN à un autre
 * visiteur. Le contournement Cloudflare pour /merci/* reste à créer côté tableau
 * de bord Cloudflare (règle Cache Bypass) — hors périmètre du thème. */
add_action('template_redirect', function () {
    if (!is_page('merci')) return;
    nocache_headers();
    do_action('litespeed_control_set_nocache');
});

/* ---------- Le contenu des pages est du HTML propre : pas de retouche automatique ---------- */
remove_filter('the_content', 'wpautop');
remove_filter('the_content', 'wptexturize');
remove_filter('the_title', 'wptexturize');

/* ---------- Nettoyage du <head> ---------- */
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'wp_shortlink_wp_head');
remove_action('wp_head', 'rest_output_link_wp_head');
remove_action('wp_head', 'wp_oembed_add_discovery_links');
remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
remove_action('wp_footer', 'wp_enqueue_global_styles', 1);
remove_action('wp_enqueue_scripts', 'wp_enqueue_classic_theme_styles');

add_filter('woocommerce_enqueue_styles', '__return_empty_array');

add_action('wp_enqueue_scripts', function () {
    foreach (['wp-block-library', 'wp-block-library-theme', 'wc-blocks-style', 'global-styles', 'classic-theme-styles'] as $h) {
        wp_dequeue_style($h);
    }
    foreach (['wc-cart-fragments', 'woocommerce', 'wc-add-to-cart', 'sourcebuster-js', 'wc-order-attribution'] as $h) {
        wp_dequeue_script($h);
    }
}, 100);

/* ---------- Feuille de style, polices, script, Pixel ---------- */
function abaya_asset_ver($rel) {
    $f = get_template_directory() . '/' . $rel;
    return file_exists($f) ? filemtime($f) : '1';
}

/* Polices : auto-hébergées dans assets/fonts/ (voir @font-face en tête de styles.css).
 * Plus aucune dépendance Google Fonts : pas de requête externe, pas de WebFontLoader,
 * pas de chaîne HTML -> CSS -> woff2. font-display: swap conservé. */

/* =====================================================================
 * CATALOGUE — ADAPTATEUR VERS LE REGISTRE DES OFFRES (mandat multi-landing)
 * Source de vérité unique partagée par :
 *   - le rendu PHP (vignettes, swatches, cartes d'offre, page /merci/) ;
 *   - la validation serveur de la route REST (whitelist couleur/taille) ;
 *   - l'injection JS via window.LANDING (grille tarifaire des bundles).
 * La définition des couleurs/tailles/bundles vit dans abaya_offers()
 * (inc-offers.php) : UNE offre = UNE entrée de données. abaya_catalog()
 * reste l'adaptateur historique consommé par les gabarits, le REST et app.js.
 *
 * Nomenclature officielle des 7 couleurs : la couleur bordeaux/grenat est
 * nommée « أحمر داكن » ; l'ancien libellé bordeaux est retiré du code.
 *
 * Stock épuisé : une couleur peut porter 'out_of_stock' => true (retrait
 * TEMPORAIRE de la vente). Elle reste affichée ; l'overlay #stock-overlay
 * côté front et la garde 6b du handler bloquent toute commande sur cette
 * déclinaison. Retour à la normale = retirer le drapeau dans le registre.
 * ===================================================================== */
function abaya_catalog($offer_id = null) {
    static $catalogs = [];
    if ($offer_id === null) $offer_id = abaya_current_offer_id();
    $offer_id = sanitize_key((string) $offer_id);
    if (isset($catalogs[$offer_id])) return $catalogs[$offer_id];

    $offer = abaya_offer_config($offer_id);
    if (!$offer) $offer = abaya_offer_config(defined('ABAYA_FRONT_OFFER') ? ABAYA_FRONT_OFFER : 'accueil');

    $catalog = [
        /* « label » = forme définie arabe (ال…) utilisée dans les textes d'alt/aria. */
        'colors'        => $offer['colors'],
        'sizes'         => $offer['sizes'],
        /* Grille tarifaire AUTORITAIRE des offres (MAD) — le serveur est la seule
         * autorité sur le prix d'une commande, le front n'est qu'un affichage. */
        'bundles'       => $offer['bundles'],
        'bundle_labels' => $offer['bundle_labels'],
    ];
    $catalogs[$offer_id] = $catalog;
    return $catalog;
}

/* Image LCP (#main-photo) : visuel défini par offre dans le registre
 * (l'offre vedette conserve le Hero Moka/Chocolat d'origine).
 * URL unique partagée entre le preload (wp_head) et la balise <img>, cache-busting filemtime. */
function abaya_lcp_image_src() {
    $offer = abaya_offer_config();
    $rel   = $offer ? (string) $offer['images']['lcp'] : 'uploads/hero-moka.jpg';
    $f   = get_template_directory() . '/' . $rel;
    $ver = file_exists($f) ? filemtime($f) : '1';
    return get_template_directory_uri() . '/' . $rel . '?v=' . $ver;
}

/* Priorité 1 : émis AVANT les autres ressources du wp_head (priorité 5),
 * pour que le navigateur découvre l'image LCP et les polices critiques en premier. */
add_action('wp_head', function () {
    if (!abaya_is_landing_page()) return;

    $base  = get_template_directory_uri();
    $fonts = $base . '/assets/fonts';
    ?>
<link rel="preload" as="image" href="<?php echo esc_url(abaya_lcp_image_src()); ?>" fetchpriority="high" />
<link rel="preload" as="font" type="font/woff2" crossorigin href="<?php echo esc_url($fonts . '/Cairo-arabic.woff2'); ?>" />
<link rel="preload" as="font" type="font/woff2" crossorigin href="<?php echo esc_url($fonts . '/DMSans-latin.woff2'); ?>" />
<?php
}, 1);

add_action('wp_head', function () {
    $base    = get_template_directory_uri();
    $favicon = 'favicon-officiel.svg'; // seul favicon du dépôt — l'ancien chemin favicon.svg, absent, produisait un 404 sur les pages légales
    ?>
<link rel="icon" href="<?php echo esc_url($base . '/uploads/' . $favicon); ?>" type="image/svg+xml" />
<?php
    /* CSS critique inline (header + hero + galerie + prix + @font-face variables)
     * puis styles.css en asynchrone : le premier rendu n'attend plus les 39 Ko de CSS.
     * Le fichier critique est généré et validé au pixel près (0 % d'écart mobile). */
    $critical_file = get_template_directory() . '/assets/css/critical.css';
    $css_url = esc_url($base . '/assets/css/styles.css?ver=' . abaya_asset_ver('assets/css/styles.css'));
    if (file_exists($critical_file)) {
        $critical = file_get_contents($critical_file);
        $critical = str_replace('__FONT_BASE__', $base . '/assets/fonts', $critical);
        echo '<style id="abaya-critical">' . $critical . '</style>' . "\n";
?>
<link rel="preload" as="style" href="<?php echo $css_url; ?>" />
<link rel="stylesheet" href="<?php echo $css_url; ?>" media="print" onload="this.media='all';" />
<noscript><link rel="stylesheet" href="<?php echo $css_url; ?>" /></noscript>
<?php
    } else {
        /* Repli sûr : pas de CSS critique -> feuille de style bloquante classique. */
?>
<link rel="stylesheet" href="<?php echo $css_url; ?>" />
<?php } ?>
<?php
    if (!abaya_is_landing_page()) return;

    $endpoint        = wp_make_link_relative(rest_url('landing/v1/create-order'));
    $review_endpoint = wp_make_link_relative(rest_url('landing/v1/submit-review'));
    $thanks          = wp_make_link_relative(home_url('/merci/'));
    $offer           = abaya_offer_config();
    $cat             = abaya_catalog($offer['id']);
    /* Mandat multi-landing : sur l'offre vedette, window.LANDING reste IDENTIQUE
     * au socle (aucune clé ajoutée — compat caches, diff racine = zéro hors
     * cache-busting). Les clés offre ne sont injectées QUE sur les landings
     * secondaires ; app.js n'envoie le paramètre `offer` que si elles sont présentes. */
    $landing_config = [
    'endpoint'        => $endpoint,
    'thanks'          => $thanks,
    'review_endpoint' => $review_endpoint,
    /* P0-1 : la grille tarifaire des bundles est injectée depuis le
     * catalogue PHP — plus aucune duplication front/serveur possible. */
    'catalog'         => [
        'colors'  => array_keys($cat['colors']),
        'sizes'   => array_values($cat['sizes']),
        'bundles' => $cat['bundles'],
    ],
    ];
    if (!abaya_is_front_offer($offer['id'])) {
        $landing_config['offer_id']   = $offer['id'];
        $landing_config['product_id'] = (int) $offer['product_id'];
        $landing_config['sku']        = '';
        if (function_exists('wc_get_product') && (int) $offer['product_id'] > 0) {
            $abaya_wc_product = wc_get_product((int) $offer['product_id']);
            if ($abaya_wc_product) $landing_config['sku'] = (string) $abaya_wc_product->get_sku();
        }
    }
    /* Tunnel post-achat (mandat ⑭) : en mode upsell (commande tunnel valide dans
     * l'URL), la soumission du formulaire AJOUTE une ligne à la commande existante
     * (route add-to-order) puis ramène à la Thank You Page personnalisée — le
     * récapitulatif est recalculé serveur. La clé `offer` cible l'offre de la
     * landing courante (vedette incluse). Clés ADDITIVES : la racine et les
     * visites sans contexte (redirigées) restent inchangées bit-à-bit. */
    $abaya_upsell_ctx = function_exists('abaya_upsell_context') ? abaya_upsell_context() : false;
    if ($abaya_upsell_ctx) {
        $landing_config['upsell_endpoint'] = wp_make_link_relative(rest_url('landing/v1/add-to-order'));
        $landing_config['upsell'] = [
            'order_id'  => (int) $abaya_upsell_ctx['order_id'],
            'order_key' => (string) $abaya_upsell_ctx['order_key'],
            'offer'     => $offer['id'],
            'return'    => (string) $abaya_upsell_ctx['merci_url'],
        ];
    }
    ?>
<script>window.LANDING = <?php echo wp_json_encode($landing_config); ?>;</script>
<script type="module" src="<?php echo esc_url($base . '/assets/js/app.js?ver=' . abaya_asset_ver('assets/js/app.js')); ?>"></script>
<?php if (defined('META_PIXEL_ID') && META_PIXEL_ID) : ?>
<script>
/* Meta Pixel : stub immédiat, fbevents.js chargé après l'événement "load" (préserve PageSpeed) */
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];
f.addEventListener('load',function(){t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)});
}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
fbq('init','<?php echo esc_js(META_PIXEL_ID); ?>');
fbq('track','PageView');
fbq('track','ViewContent',{content_type:'product',content_ids:['<?php echo (int) (abaya_is_front_offer($offer['id']) ? (defined('LANDING_PRODUCT_ID') ? LANDING_PRODUCT_ID : 0) : $offer['product_id']); ?>'],currency:'MAD',value:<?php echo (int) $cat['bundles'][1]; ?>});
</script>
<?php endif;
}, 5);

/* ---------- Meta Conversions API (CAPI) : envoi serveur de l'événement Purchase ----------
 * Actif uniquement si META_CAPI_TOKEN et META_PIXEL_ID sont renseignés dans wp-config.php.
 * Point d'intégration : le traitement de la commande déclenche l'action
 *   do_action('abaya_order_created', [
 *       'event_id'  => 'order_xxx',      // identique à celui envoyé par le Pixel (déduplication)
 *       'value'     => 299,              // montant réel de la commande en MAD
 *       'full_name' => '...', 'phone' => '...', 'city' => '...',
 *       'page_url'  => 'https://.../...',
 *   ]);
 * L'appel Meta est non bloquant (blocking=false) pour ne jamais ralentir la réponse.
 */
add_action('abaya_order_created', function ($order) {
    if (!META_CAPI_TOKEN || !META_PIXEL_ID) return;

    $user_data = [];
    if (!empty($order['phone'])) {
        $phone = preg_replace('/\D/', '', (string) $order['phone']);
        if (strpos($phone, '212') !== 0 && strlen($phone) === 10) $phone = '212' . substr($phone, 1);
        if (strlen($phone) >= 10) $user_data['ph'] = [hash('sha256', $phone)];
    }
    if (!empty($order['city'])) $user_data['ct'] = [hash('sha256', mb_strtolower(trim((string) $order['city']), 'UTF-8'))];
    if (!empty($order['full_name'])) {
        $parts = preg_split('/\s+/', trim((string) $order['full_name']), 2);
        $user_data['fn'] = [hash('sha256', mb_strtolower($parts[0], 'UTF-8'))];
        if (!empty($parts[1])) $user_data['ln'] = [hash('sha256', mb_strtolower($parts[1], 'UTF-8'))];
    }
    // Identifiants Meta collectés par le navigateur (cookies _fbp/_fbc envoyés avec la requête) : améliorent la qualité de matching CAPI.
    if (!empty($order['fbp'])) $user_data['fbp'] = (string) $order['fbp'];
    if (!empty($order['fbc'])) $user_data['fbc'] = (string) $order['fbc'];

    $event = [
        'event_name'       => 'Purchase',
        'event_time'       => time(),
        'event_id'         => (string) ($order['event_id'] ?? ''),
        'event_source_url' => (string) ($order['page_url'] ?? home_url('/')),
        'action_source'    => 'website',
        'user_data'        => $user_data,
        'custom_data'      => [
            'currency'     => 'MAD',
            'value'        => (float) ($order['value'] ?? 0),
            'content_type' => 'product',
            'content_ids'  => [(string) ($order['product_id'] ?? (defined('LANDING_PRODUCT_ID') ? LANDING_PRODUCT_ID : 0))],
        ],
    ];
    /* Correction mandat D1 : le champ « data » doit être la LISTE d'événements encodée une seule
     * fois (wp_json_encode([$event])). L'ancien code envoyait wp_json_encode(['data' => [$event]])
     * soit un double encodage — Meta attendait [event] et recevait {"data":[event]} : rejet. */
    $body = [
        'access_token' => META_CAPI_TOKEN,
        'data'         => wp_json_encode([$event]),
    ];
    if (META_TEST_EVENT_CODE) $body['test_event_code'] = META_TEST_EVENT_CODE;

    wp_remote_post(
        'https://graph.facebook.com/v19.0/' . rawurlencode(META_PIXEL_ID) . '/events',
        [
            'timeout'  => 5,
            'blocking' => false,
            'body'     => $body,
        ]
    );
});

/* =====================================================================
 * API REST du landing (correctifs mandat) :
 *   - POST /wp-json/landing/v1/create-order  : création d'une commande
 *     WooCommerce (paiement à la livraison) — corrige l'erreur 404
 *     « No route was found matching the URL and request method »
 *     (la route appelée par app.js n'était déclarée nulle part) ;
 *   - POST /wp-json/landing/v1/submit-review : persistance des avis
 *     clients en base MySQL (commentaires natifs, modération requise).
 * Routes publiques (permission_callback => '__return_true') : le visiteur
 * n'est pas connecté. Sécurité : champ piège anti-robots, limitation de
 * débit par IP, revalidation intégrale des champs côté serveur.
 * ===================================================================== */

/** Plages réseau officielles Cloudflare (source : https://www.cloudflare.com/ips/). */
function abaya_cloudflare_ranges() {
    return [
        // IPv4
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
        '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
        '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
        '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
        // IPv6
        '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32',
        '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
    ];
}

/** Vérifie qu'une IP appartient à une des plages CIDR fournies (IPv4 + IPv6). */
function abaya_ip_in_ranges($ip, array $ranges) {
    if (!filter_var($ip, FILTER_VALIDATE_IP)) return false;
    $ip_bin = @inet_pton($ip);
    if ($ip_bin === false) return false;
    foreach ($ranges as $range) {
        if (strpos($range, '/') === false) continue;
        list($net, $mask) = explode('/', $range, 2);
        $net_bin = @inet_pton($net);
        if ($net_bin === false || strlen($net_bin) !== strlen($ip_bin)) continue;
        $bits  = (int) $mask;
        $bytes = (int) floor($bits / 8);
        $rem   = $bits % 8;
        if ($bytes > strlen($ip_bin)) continue;
        if ($bytes > 0 && substr($ip_bin, 0, $bytes) !== substr($net_bin, 0, $bytes)) continue;
        if ($rem > 0) {
            $m = (0xFF << (8 - $rem)) & 0xFF;
            if ((ord($ip_bin[$bytes]) & $m) !== (ord($net_bin[$bytes]) & $m)) continue;
        }
        return true;
    }
    return false;
}

/** Adresse IP du client (durcissement P2) :
 *  - derrière Cloudflare : CF-Connecting-IP, UNIQUEMENT si REMOTE_ADDR appartient
 *    aux plages officielles CF (l'en-tête est sinon falsifiable par le client) ;
 *    le X-Forwarded-For n'est plus consulté dans ce cas (forgeable) ;
 *  - hors Cloudflare : premier IP public de X-Forwarded-For (comportement nginx
 *    historique conservé), sinon REMOTE_ADDR. */
function abaya_client_ip() {
    $remote = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
    if ($remote !== '' && abaya_ip_in_ranges($remote, abaya_cloudflare_ranges())) {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $cf_ip = trim((string) $_SERVER['HTTP_CF_CONNECTING_IP']);
            if (filter_var($cf_ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $cf_ip;
            }
        }
        return $remote;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        foreach (explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']) as $candidate) {
            $candidate = trim($candidate);
            if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $candidate;
            }
        }
    }
    return $remote;
}

/** Recherche une commande récente du landing portant le même _abaya_event_id
 * (P1-2 : idempotence sur retry réseau / double-clic).
 * La recherche est bornée aux commandes créées dans la fenêtre ($window_seconds)
 * et au quota réel du limiteur de débit (5 commandes / 10 min par IP) — le balayage
 * évite toute dépendance à la compatibilité meta_query de wc_get_orders (HPOS/CPT). */
function abaya_find_recent_order_by_event_id($event_id, $window_seconds) {
    if (!function_exists('wc_get_orders')) return null;
    $recent = wc_get_orders([
        'limit'        => 20,
        'type'         => 'shop_order',
        'date_created' => '>=' . (time() - (int) $window_seconds),
    ]);
    foreach ($recent as $candidate) {
        if ((string) $candidate->get_meta('_abaya_event_id') === (string) $event_id) return $candidate;
    }
    return null;
}

/** Limitation de débit simple par IP (transients). Retourne false si le quota ($limit sur $window_seconds) est épuisé. */
function abaya_rate_limit($bucket, $limit, $window_seconds) {
    $key  = 'abaya_rl_' . md5($bucket . '|' . abaya_client_ip());
    $hits = (int) get_transient($key);
    if ($hits >= (int) $limit) return false;
    set_transient($key, $hits + 1, (int) $window_seconds);
    return true;
}

/** Normalise un numéro marocain vers le format local 06XXXXXXXX / 07XXXXXXXX. Retourne '' si invalide. */
function abaya_normalize_phone_ma($raw) {
    $digits = preg_replace('/\D/', '', (string) $raw);
    if (strpos($digits, '00212') === 0) {
        $digits = substr($digits, 5);
    } elseif (strpos($digits, '212') === 0) {
        $digits = substr($digits, 3);
    }
    if (strlen($digits) === 10 && $digits[0] === '0') {
        $digits = substr($digits, 1);
    }
    if (!preg_match('/^[67][0-9]{8}$/', $digits)) return '';
    return '0' . $digits;
}

/** Post cible des avis : page d'accueil statique si définie, sinon produit du landing (LANDING_PRODUCT_ID). */
function abaya_reviews_target_post_id() {
    $front_id = (int) get_option('page_on_front');
    if ($front_id > 0 && get_post($front_id)) return $front_id;
    $product_id = (int) (defined('LANDING_PRODUCT_ID') ? LANDING_PRODUCT_ID : 15);
    if ($product_id > 0 && get_post($product_id)) return $product_id;
    return 0;
}

/* =====================================================================
 * TUNNEL POST-ACHAT (mandat « fix/post-purchase-navigation-flow »)
 * ---------------------------------------------------------------------
 * Résolution partagée d'une commande tunnel à partir du duo order+key.
 * Gardes identiques au récapitulatif /merci/ (inchangées) : comparaison
 * timing-safe hash_equals de la clé, commande de moins de 48 h, provenance
 * du tunnel (created_via = landing-abaya). Source unique consommée par
 * /merci/, le contexte upsell des landings secondaires et la route
 * REST add-to-order — plus aucune duplication de cette logique.
 * ===================================================================== */
function abaya_resolve_tunnel_order($order_id, $order_key) {
    if (!function_exists('wc_get_order')) return null;
    $order_id  = absint($order_id);
    $order_key = sanitize_text_field((string) $order_key);
    if ($order_id < 1 || $order_key === '') return null;
    $abaya_candidate = wc_get_order($order_id);
    if (!$abaya_candidate || !hash_equals((string) $abaya_candidate->get_order_key(), $order_key)) return null;
    $abaya_created = $abaya_candidate->get_date_created();
    $abaya_age     = $abaya_created ? (time() - $abaya_created->getTimestamp()) : PHP_INT_MAX;
    if ($abaya_age > 48 * HOUR_IN_SECONDS || $abaya_candidate->get_created_via() !== 'landing-abaya') return null;
    return $abaya_candidate;
}

/** Contexte upsell d'une landing déverrouillée depuis la Thank You Page
 *  (mandat ⑭) : la commande tunnel référencée par ?order=&key= est-elle
 *  valide ? Retourne ['order', 'order_id', 'order_key', 'merci_url'] ou false.
 *  La racine (offre vedette) est ÉLIGIBLE : c'est la cible de la 3e carte
 *  upsell quand la commande provient d'une offre secondaire — le rendu par
 *  défaut (sans duo dans l'URL) reste inchangé bit-à-bit (compat caches v2.0).
 *  Le résultat est mémoïsé par requête (en-tête + gabarit + config JS
 *  consomment la même résolution). merci_url = Thank You Page personnalisée. */
function abaya_upsell_context() {
    static $abaya_upsell_ctx = null;
    if ($abaya_upsell_ctx !== null) return $abaya_upsell_ctx;
    $abaya_upsell_ctx = false;
    if (is_front_page()) {
        $abaya_slug = defined('ABAYA_FRONT_OFFER') ? ABAYA_FRONT_OFFER : 'accueil';
    } elseif (is_page()) {
        $abaya_slug = (string) get_post_field('post_name', get_queried_object_id());
    } else {
        return $abaya_upsell_ctx;
    }
    if (!isset(abaya_offers()[$abaya_slug])) return $abaya_upsell_ctx;
    $abaya_order_id  = isset($_GET['order']) ? absint($_GET['order']) : 0;
    $abaya_order_key = isset($_GET['key']) ? sanitize_text_field((string) wp_unslash($_GET['key'])) : '';
    $abaya_order     = abaya_resolve_tunnel_order($abaya_order_id, $abaya_order_key);
    if (!$abaya_order) return $abaya_upsell_ctx;
    $abaya_upsell_ctx = [
        'order'     => $abaya_order,
        'order_id'  => (int) $abaya_order->get_id(),
        'order_key' => (string) $abaya_order->get_order_key(),
        'merci_url' => home_url('/merci/') . '?order=' . rawurlencode((string) $abaya_order->get_id()) . '&key=' . rawurlencode((string) $abaya_order->get_order_key()),
    ];
    return $abaya_upsell_ctx;
}

/* ---------- Landings déverrouillées : déverrouillage par commande tunnel ----------
 * Mandat ⑭ (isolation des landings d'upsell) : les landings secondaires sont
 * TOTALEMENT cachées du site public et des publicités.
 *   - SANS duo order+key valide (visite directe, publicité, robot) : la page
 *     n'est JAMAIS affichée — redirection 302 vers l'offre vedette (la racine) ;
 *   - AVEC un contexte upsell valide (arrivée depuis les cartes upsell de la
 *     Thank You Page) : rendu de la landing + en-têtes no-cache — l'URL porte
 *     le duo personnel order+key, aucun cache partagé ne doit la servir
 *     (même posture que /merci/).
 * La racine reste PUBLIQUE (offre vedette, publicités) : pas de redirection —
 * mais un duo valide dans son URL active le mode upsell ET impose no-cache
 * (les clés personnalitées ne doivent jamais être servies depuis un cache
 * partagé ; le rendu par défaut sans duo reste inchangé et cacheable).
 * v3.23 (mandat « protocole mandat 4P LP » — single-upsell cleanup) : les
 * slugs RETIRÉS du registre (abaya_retired_offers() — variantes superflues
 * produit-2/produit-3) sont redirigés en 301 PERMANENT vers la racine AVANT
 * tout autre traitement : les pages WordPress historiques peuvent subsister
 * en base (semis « ne jamais écraser »), mais aucun rendu n'est plus servi —
 * les anciens liens publicitaires restent fonctionnels (redirection
 * permanente, cacheable CDN). */
function abaya_gate_secondary_landing() {
    if (!is_page()) {
        if (is_front_page() && abaya_upsell_context()) {
            nocache_headers();
            do_action('litespeed_control_set_nocache');
        }
        return;
    }
    $abaya_slug = (string) get_post_field('post_name', get_queried_object_id());
    /* v3.23 : variante retirée du registre → redirection 301 permanente. */
    if (in_array($abaya_slug, abaya_retired_offers(), true)) {
        wp_safe_redirect(home_url('/'), 301);
        exit;
    }
    if (!isset(abaya_offers()[$abaya_slug]) || abaya_is_front_offer($abaya_slug)) return;
    if (abaya_upsell_context()) {
        nocache_headers();
        do_action('litespeed_control_set_nocache');
        return;
    }
    wp_safe_redirect(home_url('/'), 302);
    exit;
}
add_action('template_redirect', 'abaya_gate_secondary_landing', 5);

/** Rendu serveur d'une carte d'avis — structure DOM identique à celle construite par app.js (.review-card).
 *  Inclut les réponses validées de la boutique (commentaires enfants approuvés) sous chaque avis client,
 *  avec un en-tête amical « تقدير متبادل من فريق Abaya Collection » et le favicon de la boutique.
 *  Mandat 4P LP (fix/review-form-fields-and-stars) : affichage « name — city » en bordeaux sous les étoiles,
 *  étoiles dorées volumineuses à sommets arrondis (stroke-linejoin: round + drop-shadow). */
function abaya_render_review_card($comment) {
    /* ÉTANCHÉITÉ stricte (mandat « fix/rating-comments-decoupling ») : le listing
     * « Avis Clients » n'affiche QUE les avis textuels validés par l'administrateur.
     *  1) rendu conditionné au statut de modération (comment_approved = 1 exigé) ;
     *  2) texte réel exigé — une évaluation d'étoiles seules (contenu vide ou
     *     placeholder du 1-clic) alimente le score global sous le H1 mais ne
     *     génère JAMAIS de carte partielle/vide dans le listing. La liaison
     *     relationnelle note↔commentaire (même enregistrement + méta « rating »)
     *     est préservée en base ; seuls les rendus sont découplés. */
    if ((int) $comment->comment_approved !== 1) return;
    $abaya_card_text = trim((string) $comment->comment_content);
    if ($abaya_card_text === '' || $abaya_card_text === 'تقييم بنقرة واحدة') return;
    $rating = (int) get_comment_meta($comment->comment_ID, 'rating', true);
    if ($rating < 1 || $rating > 5) return;

    /* Override éphémère ultra-léger pour avis existants sans nom/ville stockés en BDD.
     * Coût : 1 lookup array par avis rendu (≈ 0 µs). Aucune requête SQL, aucun impact landing.
     * Pour ajouter un nouvel avis legacy : renseigner le comment_ID et les valeurs.
     * Une fois la BDD mise à jour manuellement via l'admin WP, l'entrée peut rester (sans effet) ou être retirée. */
    static $abaya_named_overrides = [
        // <ID_AVIS_NOHA> => ['name' => 'Noha', 'city' => 'Casa'],
    ];

    /* Récupération du nom : override → comment_meta reviewer_name → comment_author → repli « عميل ». */
    $abaya_reviewer_name = '';
    $abaya_reviewer_city = '';
    if (isset($abaya_named_overrides[$comment->comment_ID])) {
        $abaya_reviewer_name = $abaya_named_overrides[$comment->comment_ID]['name'];
        $abaya_reviewer_city = $abaya_named_overrides[$comment->comment_ID]['city'];
    }
    if ($abaya_reviewer_name === '') {
        $abaya_reviewer_name = (string) get_comment_meta($comment->comment_ID, 'reviewer_name', true);
    }
    if ($abaya_reviewer_city === '') {
        $abaya_reviewer_city = (string) get_comment_meta($comment->comment_ID, 'reviewer_city', true);
    }
    if ($abaya_reviewer_name === '' && $comment->comment_author !== '' && $comment->comment_author !== 'عميل') {
        $abaya_reviewer_name = $comment->comment_author;
    }

    echo '<article class="review-card">';
    echo '<div class="review-card-header">';
    echo '<span class="review-card-stars" aria-label="' . esc_attr($rating) . ' / 5">';
    for ($i = 0; $i < 5; $i++) {
        /* Étoile dorée volumineuse à sommets arrondis : path identique + stroke + stroke-linejoin: round
         * + drop-shadow CSS pour l'effet volumineux. Pas de path SVG modifié (compat préservée). */
        echo '<svg class="review-star' . ($i >= $rating ? ' is-empty' : '') . '" viewBox="0 0 24 24" aria-hidden="true"><path d="m12 2.5 2.9 5.9 6.5.9-4.7 4.6 1.1 6.5-5.8-3.1-5.8 3.1 1.1-6.5-4.7-4.6 6.5-.9z"/></svg>';
    }
    echo '</span>';
    echo '<span class="review-card-score">' . esc_html($rating) . '/5</span>';
    echo '</div>';

    /* Ligne « name — city » en bordeaux (charte Thank You Page) — uniquement si au moins un des deux est présent. */
    if ($abaya_reviewer_name !== '' || $abaya_reviewer_city !== '') {
        echo '<p class="review-card-author">';
        if ($abaya_reviewer_name !== '') {
            echo '<strong>' . esc_html($abaya_reviewer_name) . '</strong>';
        }
        if ($abaya_reviewer_name !== '' && $abaya_reviewer_city !== '') {
            echo ' — <span class="review-card-city">' . esc_html($abaya_reviewer_city) . '</span>';
        } elseif ($abaya_reviewer_city !== '') {
            echo '<span class="review-card-city">' . esc_html($abaya_reviewer_city) . '</span>';
        }
        echo '</p>';
    }

    echo '<p class="review-card-comment">' . esc_html(get_comment_text($comment)) . '</p>';

    /* Réponses de la boutique : commentaires enfants approuvés (modération admin WordPress).
     * Récupérés sans filtre de type — l'admin peut répondre en type 'comment' ou 'review'.
     * En-tête amical + favicon boutique + texte, dans un bloc indenté (RTL → marge à droite). */
    $abaya_replies = get_comments([
        'parent' => (int) $comment->comment_ID,
        'status' => 'approve',
        'order'  => 'ASC',
    ]);
    if (!empty($abaya_replies)) {
        $abaya_favicon = esc_url(get_template_directory_uri() . '/uploads/favicon-officiel.svg');
        foreach ($abaya_replies as $abaya_reply) {
            echo '<div class="review-card-reply">';
            echo '<div class="review-card-reply-header">';
            echo '<img class="review-card-reply-logo" src="' . $abaya_favicon . '" alt="" width="18" height="18" loading="lazy" decoding="async" aria-hidden="true" />';
            echo '<span class="review-card-reply-title">تقدير متبادل من فريق Abaya Collection</span>';
            echo '</div>';
            echo '<p class="review-card-reply-text">' . esc_html(get_comment_text($abaya_reply)) . '</p>';
            echo '</div>';
        }
    }

    echo '</article>';
}

/* ---------- Route 1 : création de commande WooCommerce (COD) ---------- */

function abaya_handle_create_order(WP_REST_Request $request) {
    // 1) Champ piège anti-robots (input « extra_note » masqué côté front) : doit rester vide.
    if (trim((string) $request->get_param('website_hp')) !== '') {
        return new WP_Error('abaya_spam', 'تم رفض الطلب.', ['status' => 400]);
    }

    // 2) WooCommerce doit être actif : toute la suite dépend de wc_create_order().
    if (!function_exists('wc_create_order')) {
        return new WP_Error('abaya_no_woocommerce', 'خطأ في الخدمة. يرجى المحاولة لاحقاً.', ['status' => 500]);
    }

    // 2b) Offre (mandat multi-landing) : paramètre `offer` (slug) — repli
    //     `offer_id` (compat prototype), ABSENT = offre vedette (compat caches).
    //     Le produit est résolu depuis le registre (product_id par offre,
    //     repli LANDING_PRODUCT_ID pour la vedette). Contrat REST ADDITIF :
    //     aucune clé renommée ni retirée.
    $offer_param = sanitize_key(trim((string) $request->get_param('offer')));
    if ($offer_param === '') $offer_param = sanitize_key(trim((string) $request->get_param('offer_id')));
    if ($offer_param === '') $offer_param = defined('ABAYA_FRONT_OFFER') ? ABAYA_FRONT_OFFER : 'accueil';
    $offer = abaya_offer_config($offer_param);
    if (!$offer || (int) $offer['product_id'] < 1) {
        return new WP_Error('abaya_bad_product', 'خطأ في إعداد العرض. يرجى الاتصال بنا.', ['status' => 500]);
    }
    $product_id = (int) $offer['product_id'];

    // 3) Idempotence (P1-2) : si une commande du landing a déjà été créée
    //    avec le même event_id il y a moins de 10 minutes (retry réseau, double-clic),
    //    la réutiliser et renvoyer la même réponse SANS créer de doublon ni renvoyer
    //    un second événement Purchase CAPI.
    $client_event_id = sanitize_text_field((string) $request->get_param('event_id'));
    if ($client_event_id !== '') {
        $existing = abaya_find_recent_order_by_event_id($client_event_id, 10 * MINUTE_IN_SECONDS);
        if ($existing) {
            return rest_ensure_response([
                'success'    => true,
                'order_id'   => $existing->get_id(),
                'value'      => (float) $existing->get_total(),
                'event_id'   => $client_event_id,
                'product_id' => (int) ($existing->get_meta('_abaya_product_id') ?: $product_id),
                'offer'      => (string) ($existing->get_meta('_abaya_offer') ?: $offer['id']),
                'order_key'  => $existing->get_order_key(),
                'duplicate'  => true,
            ]);
        }
    }

    // 4) Anti-flood : maximum 5 commandes par tranche de 10 minutes et par IP.
    //    (placé APRÈS l'idempotence : un retry du même event_id ne consomme pas le quota.)
    if (!abaya_rate_limit('order', 5, 10 * MINUTE_IN_SECONDS)) {
        return new WP_Error('abaya_rate_limited', 'عدد كبير من المحاولات. يرجى المحاولة بعد قليل.', ['status' => 429]);
    }

    // 5) Revalidation serveur des champs (mêmes règles que app.js).
    $full_name = sanitize_text_field((string) $request->get_param('full_name'));
    $city      = sanitize_text_field((string) $request->get_param('city'));
    $address   = sanitize_textarea_field((string) $request->get_param('address'));
    $phone     = abaya_normalize_phone_ma((string) $request->get_param('phone'));

    if (mb_strlen($full_name) < 3) {
        return new WP_Error('abaya_invalid_field', 'يرجى إدخال الاسم الكامل.', ['status' => 422]);
    }
    if ($phone === '') {
        return new WP_Error('abaya_invalid_field', 'أدخلي رقم هاتف مغربي صحيحاً.', ['status' => 422]);
    }
    if (mb_strlen($city) < 2) {
        return new WP_Error('abaya_invalid_field', 'يرجى اختيار المدينة.', ['status' => 422]);
    }
    if (mb_strlen($address) < 4) {
        return new WP_Error('abaya_invalid_field', 'يرجى إدخال العنوان الكامل.', ['status' => 422]);
    }

    // 6) Whitelist catalogue (P0-2) : la couleur et la taille DOIVENT
    //    exister dans le catalogue DE CETTE OFFRE (registre). Route publique :
    //    aucune confiance dans le JSON client. Une couleur d'une autre offre
    //    est rejetée en 422 — chaque offre dispose de sa propre whitelist.
    $cat   = abaya_catalog($offer['id']);
    $color = sanitize_text_field((string) $request->get_param('color'));
    $size  = sanitize_text_field((string) $request->get_param('size'));
    if (!isset($cat['colors'][$color]) || !in_array($size, $cat['sizes'], true)) {
        return new WP_Error('abaya_invalid_field', 'يرجى اختيار اللون والمقاس بشكل صحيح.', ['status' => 422]);
    }

    // 6b) Stock épuisé (v1.10) : une couleur portant le drapeau out_of_stock
    //     du catalogue est refusée côté serveur aussi — la route est publique,
    //     le blocage front n'est qu'une commodité, le catalogue fait foi.
    if (!empty($cat['colors'][$color]['out_of_stock'])) {
        return new WP_Error('abaya_out_of_stock', 'غير متوفر حالياً في هذا اللون — يرجى اختيار لون آخر.', ['status' => 422]);
    }

    // 7) Prix AUTORITAIRE du serveur (P0-2) : la grille abaya_catalog() remplace
    //    l'ancien calcul « prix produit × quantité » qui facturait 598/897 MAD
    //    pour les offres 2/3 pièces promises à 499/699 MAD.
    $quantity = max(1, min(3, (int) $request->get_param('quantity')));
    if (!isset($cat['bundles'][$quantity])) {
        return new WP_Error('abaya_invalid_field', 'يرجى اختيار العرض المناسب.', ['status' => 422]);
    }
    $price = (float) $cat['bundles'][$quantity];

    $product = $product_id > 0 ? wc_get_product($product_id) : false;
    if (!$product) {
        // Le prix du produit WC n'est plus consulté : la grille du catalogue fait foi.
        return new WP_Error('abaya_bad_product', 'خطأ في الخدمة. يرجى المحاولة لاحقاً.', ['status' => 500]);
    }

    // 8) event_id de déduplication Pixel/CAPI : celui du navigateur, sinon généré serveur (réutilisé par le Pixel).
    $event_id = $client_event_id !== '' ? $client_event_id : 'order_' . time() . '_' . wp_generate_password(8, false, false);
    $page_url = esc_url_raw((string) $request->get_param('page_url'));

    // 9) Création de la commande WooCommerce officielle, en paiement à la livraison.
    $order = wc_create_order(['status' => 'pending', 'created_via' => 'landing-abaya']);
    if (is_wp_error($order) || !is_object($order)) {
        return new WP_Error('abaya_order_failed', 'خطأ في الخدمة. يرجى المحاولة لاحقاً.', ['status' => 500]);
    }
    // Prix pack imposé sur la LIGNE d'article (subtotal + total).
    $item_id = $order->add_product($product, $quantity, ['subtotal' => $price, 'total' => $price]);
    $item    = $item_id ? $order->get_item($item_id) : null;
    if ($item) {
        // P0-2 : taille et couleur inscrites en clair sur la ligne
        // d'article (Item Meta), visibles dans l'admin WooCommerce et les e-mails.
        $item->add_meta_data('اللون', $color, true);
        $item->add_meta_data('المقاس', $size, true);
        // Mandat multi-landing : traçabilité de l'offre sur la ligne d'article.
        $item->add_meta_data('Offre', $offer['name'], true);
        if ((string) $product->get_sku() !== '') $item->add_meta_data('SKU', (string) $product->get_sku(), true);
        $item->save();
    }

    $name_parts = preg_split('/\s+/', $full_name, 2);
    $billing    = [
        'first_name' => $name_parts[0],
        'last_name'  => isset($name_parts[1]) ? $name_parts[1] : '',
        'phone'      => $phone,
        'address_1'  => $address,
        'city'       => $city,
        'country'    => 'MA',
    ];
    $order->set_address($billing, 'billing');
    $order->set_address([
        'first_name' => $billing['first_name'],
        'last_name'  => $billing['last_name'],
        'address_1'  => $address,
        'city'       => $city,
        'country'    => 'MA',
    ], 'shipping');
    $order->set_payment_method('cod');
    $order->set_payment_method_title('الدفع عند الاستلام');
    $order->calculate_totals();
    $order->update_status('processing', 'Commande COD créée depuis le formulaire du landing (Abaya Collection).');

    // Note de commande lisible par le service client (P0-2).
    $order->add_order_note(sprintf(
        'Tunnel landing (%1$s) — اللون : %2$s · المقاس : %3$s · Offre : %4$d pièce(s) — %5$.0f MAD · event_id : %6$s',
        $offer['id'],
        $color,
        $size,
        $quantity,
        $price,
        $event_id
    ));

    // 10) Persistance des métadonnées marketing (event_id, page_url, utm_*, _fbp/_fbc)
    //    et de configuration. _abaya_color/_abaya_size conservées au niveau commande
    //    (rétrocompatibilité rapports + page /merci/), les valeurs sont désormais
    //    validées et également portées par la ligne d'article (Item Meta).
    $order->update_meta_data('_abaya_event_id', $event_id);
    $order->update_meta_data('_abaya_page_url', $page_url);
    $order->update_meta_data('_abaya_color', $color);
    $order->update_meta_data('_abaya_size', $size);
    /* Mandat multi-landing : offre d'origine de la commande (lue par /merci/
     * pour le cross-sell et le code promo). _abaya_offer = clé canonique du
     * mandat ; _abaya_offer_id/_abaya_offer_name/_abaya_product_id = alias
     * documentaires conservés pour la traçabilité. */
    $order->update_meta_data('_abaya_offer', $offer['id']);
    $order->update_meta_data('_abaya_offer_id', $offer['id']);
    $order->update_meta_data('_abaya_offer_name', $offer['name']);
    $order->update_meta_data('_abaya_product_id', $product_id);
    $order->update_meta_data('_abaya_client_ip', abaya_client_ip());
    foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'] as $utm_key) {
        $utm_value = sanitize_text_field((string) $request->get_param($utm_key));
        if ($utm_value !== '') $order->update_meta_data('_abaya_' . $utm_key, $utm_value);
    }
    if (!empty($_COOKIE['_fbp'])) $order->update_meta_data('_abaya_fbp', sanitize_text_field((string) $_COOKIE['_fbp']));
    if (!empty($_COOKIE['_fbc'])) $order->update_meta_data('_abaya_fbc', sanitize_text_field((string) $_COOKIE['_fbc']));
    $order->save();

    // 11) Déclenche le listener CAPI existant (envoi serveur Purchase, non bloquant, dédupliqué par event_id).
    do_action('abaya_order_created', [
        'event_id'   => $event_id,
        'value'      => (float) $order->get_total(),
        'full_name'  => $full_name,
        'phone'      => $phone,
        'city'       => $city,
        'page_url'   => $page_url !== '' ? $page_url : home_url('/'),
        'fbp'        => isset($_COOKIE['_fbp']) ? (string) $_COOKIE['_fbp'] : '',
        'fbc'        => isset($_COOKIE['_fbc']) ? (string) $_COOKIE['_fbc'] : '',
        'product_id' => $product_id,
        'order_id'   => $order->get_id(),
    ], $order);

    // 12) Réponse au format exact consommé par app.js, enrichie de order_key :
    //     la redirection /merci/?order=ID&key=KEY sécurise l'accès au récapitulatif
    //     dynamique (page-merci.php) sans exposer de données via un simple ?order=ID.
    //     Mandat multi-landing : clé `offer` ADDITIONNELLE (aucune clé renommée/retirée).
    return rest_ensure_response([
        'success'    => true,
        'order_id'   => $order->get_id(),
        'value'      => (float) $order->get_total(),
        'event_id'   => $event_id,
        'product_id' => $product_id,
        'offer'      => $offer['id'],
        'order_key'  => $order->get_order_key(),
    ]);
}

/* ---------- Route 2 : ajout upsell à une commande tunnel existante (mandat ⑭) ----------
 * Consommée par les landings secondaires déverrouillées depuis la Thank You
 * Page (window.LANDING.upsell) : la soumission complète la COMMANDE EXISTANTE
 * au lieu de créer un ticket séparé — le récapitulatif /merci/ se met à jour
 * dynamiquement au retour. Mêmes gardes que create-order : champ piège,
 * prix AUTORITAIRE du registre, whitelist catalogue de l'offre, anti-flood.
 * Idempotence par event_id porté en méta de ligne (retry / double-clic). */

function abaya_handle_add_to_order(WP_REST_Request $request) {
    // 1) Champ piège anti-robots (input « extra_note » masqué côté front) : doit rester vide.
    if (trim((string) $request->get_param('website_hp')) !== '') {
        return new WP_Error('abaya_spam', 'تم رفض الطلب.', ['status' => 400]);
    }

    // 2) WooCommerce doit être actif.
    if (!function_exists('wc_get_order') || !function_exists('wc_get_product')) {
        return new WP_Error('abaya_no_woocommerce', 'خطأ في الخدمة. يرجى المحاولة لاحقاً.', ['status' => 500]);
    }

    // 3) Commande tunnel : résolution partagée (hash_equals + < 48 h + created_via).
    $order = abaya_resolve_tunnel_order($request->get_param('order_id'), $request->get_param('order_key'));
    if (!$order) {
        return new WP_Error('abaya_bad_order', 'انتهت صلاحية رابط الطلب. يرجى الاتصال بنا.', ['status' => 403]);
    }

    // 4) Anti-flood : même quota que la création de commande (5 / 10 min / IP).
    if (!abaya_rate_limit('order', 5, 10 * MINUTE_IN_SECONDS)) {
        return new WP_Error('abaya_rate_limited', 'عدد كبير من المحاولات. يرجى المحاولة بعد قليل.', ['status' => 429]);
    }

    // 5) Offre upsell : paramètre `offer` obligatoire et connu du registre.
    $offer_param = sanitize_key(trim((string) $request->get_param('offer')));
    $offer = $offer_param !== '' ? abaya_offer_config($offer_param) : null;
    if (!$offer || (int) $offer['product_id'] < 1) {
        return new WP_Error('abaya_bad_product', 'خطأ في إعداد العرض. يرجى الاتصال بنا.', ['status' => 500]);
    }
    $product_id = (int) $offer['product_id'];

    // 6) Idempotence (P1-2, portée commande) : une ligne ajoutée porte son
    //    event_id en méta — le même event_id ne crée JAMAIS une seconde ligne
    //    (retry réseau, double-clic). Réponse idempotente sans recalcul.
    $client_event_id = sanitize_text_field((string) $request->get_param('event_id'));
    if ($client_event_id !== '') {
        foreach ($order->get_items() as $abaya_existing_item) {
            if ((string) $abaya_existing_item->get_meta('_abaya_upsell_event') === $client_event_id) {
                return rest_ensure_response([
                    'success'     => true,
                    'order_id'    => $order->get_id(),
                    'value'       => (float) $order->get_total(),
                    'added_value' => (float) $abaya_existing_item->get_total(),
                    'event_id'    => $client_event_id,
                    'product_id'  => $product_id,
                    'offer'       => $offer['id'],
                    'order_key'   => $order->get_order_key(),
                    'duplicate'   => true,
                ]);
            }
        }
    }

    // 7) Whitelist catalogue de CETTE offre (P0-2) + stock épuisé (v1.10).
    $cat   = abaya_catalog($offer['id']);
    $color = sanitize_text_field((string) $request->get_param('color'));
    $size  = sanitize_text_field((string) $request->get_param('size'));
    if (!isset($cat['colors'][$color]) || !in_array($size, $cat['sizes'], true)) {
        return new WP_Error('abaya_invalid_field', 'يرجى اختيار اللون والمقاس بشكل صحيح.', ['status' => 422]);
    }
    if (!empty($cat['colors'][$color]['out_of_stock'])) {
        return new WP_Error('abaya_out_of_stock', 'غير متوفر حالياً في هذا اللون — يرجى اختيار لون آخر.', ['status' => 422]);
    }

    // 8) Prix AUTORITAIRE du serveur : la grille abaya_catalog() fait foi.
    $quantity = max(1, min(3, (int) $request->get_param('quantity')));
    if (!isset($cat['bundles'][$quantity])) {
        return new WP_Error('abaya_invalid_field', 'يرجى اختيار العرض المناسب.', ['status' => 422]);
    }
    $price = (float) $cat['bundles'][$quantity];

    $product = wc_get_product($product_id);
    if (!$product) {
        return new WP_Error('abaya_bad_product', 'خطأ في الخدمة. يرجى المحاولة لاحقاً.', ['status' => 500]);
    }

    // 9) event_id de déduplication Pixel/CAPI : navigateur, sinon généré serveur.
    $event_id = $client_event_id !== '' ? $client_event_id : 'upsell_' . time() . '_' . wp_generate_password(8, false, false);
    $page_url = esc_url_raw((string) $request->get_param('page_url'));

    // 10) Ligne additionnelle sur la MÊME commande (Item Meta traçable, mêmes
    //     conventions lisibles que create-order + traçabilité upsell).
    $item_id = $order->add_product($product, $quantity, ['subtotal' => $price, 'total' => $price]);
    $item    = $item_id ? $order->get_item($item_id) : null;
    if (!$item) {
        return new WP_Error('abaya_order_failed', 'خطأ في الخدمة. يرجى المحاولة لاحقاً.', ['status' => 500]);
    }
    $item->add_meta_data('اللون', $color, true);
    $item->add_meta_data('المقاس', $size, true);
    $item->add_meta_data('Offre', $offer['name'], true);
    if ((string) $product->get_sku() !== '') $item->add_meta_data('SKU', (string) $product->get_sku(), true);
    $item->add_meta_data('_abaya_upsell_event', $event_id, true);
    $item->add_meta_data('_abaya_upsell_offer', $offer['id'], true);
    $item->save();

    // 11) Totaux recalculés sur la commande existante + note service client.
    $order->calculate_totals();
    $order->add_order_note(sprintf(
        'Upsell tunnel (%1$s) — اللون : %2$s · المقاس : %3$s · %4$d pièce(s) — %5$.0f MAD — ajouté à la commande #%6$d · event_id : %7$s',
        $offer['id'],
        $color,
        $size,
        $quantity,
        $price,
        $order->get_id(),
        $event_id
    ));
    $order->save();

    // 12) CAPI : Purchase additionnel (valeur = ligne ajoutée, event_id dédupliqué
    //     avec le Pixel) — le listener existant est réutilisé tel quel.
    do_action('abaya_order_created', [
        'event_id'   => $event_id,
        'value'      => $price,
        'full_name'  => trim((string) $order->get_billing_first_name() . ' ' . (string) $order->get_billing_last_name()),
        'phone'      => (string) $order->get_billing_phone(),
        'city'       => (string) $order->get_billing_city(),
        'page_url'   => $page_url !== '' ? $page_url : home_url('/'),
        'fbp'        => isset($_COOKIE['_fbp']) ? (string) $_COOKIE['_fbp'] : '',
        'fbc'        => isset($_COOKIE['_fbc']) ? (string) $_COOKIE['_fbc'] : '',
        'product_id' => $product_id,
        'order_id'   => $order->get_id(),
    ], $order);

    // 13) Réponse : value = NOUVEAU total de la commande — le retour /merci/
    //     recalcule le récapitulatif depuis la base (source unique).
    return rest_ensure_response([
        'success'     => true,
        'order_id'    => $order->get_id(),
        'value'       => (float) $order->get_total(),
        'added_value' => $price,
        'event_id'    => $event_id,
        'product_id'  => $product_id,
        'offer'       => $offer['id'],
        'order_key'   => $order->get_order_key(),
        'duplicate'   => false,
    ]);
}

/* ---------- Route 3 : soumission d'avis clients (persistance MySQL + modération
 *            intelligente — mandat « Thank You Page Redesign ») ---------- */
function abaya_handle_submit_review(WP_REST_Request $request) {
    // 1) Anti-flood : maximum 3 avis par tranche de 10 minutes et par IP.
    if (!abaya_rate_limit('review', 3, 10 * MINUTE_IN_SECONDS)) {
        return new WP_Error('abaya_rate_limited', 'عدد كبير من المحاولات. يرجى المحاولة بعد قليل.', ['status' => 429]);
    }

    // 2) Validation du contenu — le TEXTE est désormais OPTIONNEL (mandat :
    //    « étoiles seules » = 1-clic valide) ; le formulaire avis de la landing
    //    continue d'exiger le texte côté client et suit le même chemin serveur.
    $rating        = (int) $request->get_param('rating');
    $comment       = trim(sanitize_textarea_field((string) $request->get_param('comment')));
    $reviewer_name = trim(sanitize_text_field((string) $request->get_param('reviewer_name')));
    $reviewer_city = trim(sanitize_text_field((string) $request->get_param('reviewer_city')));
    if ($rating < 1 || $rating > 5) {
        return new WP_Error('abaya_invalid_field', 'يرجى اختيار عدد النجوم.', ['status' => 422]);
    }
    if ($comment !== '' && (mb_strlen($comment) < 3 || mb_strlen($comment) > 2000)) {
        return new WP_Error('abaya_invalid_field', 'يرجى كتابة رأيك في المنتج.', ['status' => 422]);
    }
    if (mb_strlen($reviewer_name) < 2 || mb_strlen($reviewer_name) > 50) {
        return new WP_Error('abaya_invalid_field', 'يرجى إدخال اسمك.', ['status' => 422]);
    }
    if (mb_strlen($reviewer_city) < 2 || mb_strlen($reviewer_city) > 50) {
        return new WP_Error('abaya_invalid_field', 'يرجى اختيار المدينة.', ['status' => 422]);
    }

    // 3) Post cible : page d'accueil statique si définie, sinon produit du landing.
    $target_id = abaya_reviews_target_post_id();
    if (!$target_id) {
        return new WP_Error('abaya_no_target', 'خطأ في الخدمة. يرجى المحاولة لاحقاً.', ['status' => 500]);
    }

    // 3b) Mandat multi-landing : offre d'origine de l'avis (méta informationnelle,
    //     facultative — la moyenne du badge agrège TOUTES les notes attribuées,
    //     indépendamment du corps du commentaire et de la modération).
    $review_offer = sanitize_key((string) $request->get_param('offer'));
    if ($review_offer === '') $review_offer = sanitize_key((string) $request->get_param('offer_id'));
    if ($review_offer !== '' && !isset(abaya_offers()[$review_offer])) $review_offer = '';

    // 3c) Contexte commande (pop-up /merci/) : order_id + order_key. La clé est
    //     revalidée en timing-safe (hash_equals) et la commande doit provenir du
    //     tunnel — sans ce duo valide, AUCUNE auto-approbation (défense anti-spam).
    $review_order    = null;
    $order_id_param  = absint($request->get_param('order_id'));
    $order_key_param = sanitize_text_field((string) $request->get_param('order_key'));
    if ($order_id_param > 0 && $order_key_param !== '' && function_exists('wc_get_order')) {
        $abaya_candidate = wc_get_order($order_id_param);
        if ($abaya_candidate
            && hash_equals((string) $abaya_candidate->get_order_key(), $order_key_param)
            && $abaya_candidate->get_created_via() === 'landing-abaya') {
            $review_order = $abaya_candidate;
        }
    }

    // 3d) RÈGLE DU MAXIMUM (mandat) : si l'utilisatrice modifie son choix de note,
    //     le système enregistre et conserve la valeur la plus haute exprimée :
    //     max(note_existante, nouvelle_note). L'avis existant est retrouvé par la
    //     méta « review_order_id » (un seul avis par commande, tout statut).
    $existing_id     = 0;
    $existing_rating = 0;
    $existing_approved = 0;
    $existing_has_text = false;
    if ($review_order) {
        $abaya_found = get_comments([
            'post_id'    => $target_id,
            'type'       => 'review',
            'status'     => 'all',
            'meta_key'   => 'review_order_id',
            'meta_value' => (string) $review_order->get_id(),
            'number'     => 1,
        ]);
        if (!empty($abaya_found)) {
            $existing_id       = (int) $abaya_found[0]->comment_ID;
            $existing_rating   = (int) get_comment_meta($existing_id, 'rating', true);
            $existing_approved = (int) $abaya_found[0]->comment_approved;
            /* Le texte déjà présent (≠ commentaire automatique du 1-clic) maintient
             * l'avis en modération, même si une re-soumission d'étoiles seules suit. */
            $existing_content  = trim((string) $abaya_found[0]->comment_content);
            $existing_has_text = ($existing_content !== '' && $existing_content !== 'تقييم بنقرة واحدة');
        }
    }
    $final_rating = max($existing_rating, $rating);
    $has_text     = ($comment !== '');

    // 4) MODÉRATION INTELLIGENTE + DÉCOUPLAGE (mandat fix/rating-comments-decoupling) :
    //      - étoiles seules + commande validée → comment_approved = 1 : la note
    //        alimente immédiatement la moyenne sous le H1 via abaya_review_summary()
    //        (agrégat découplé de la modération) — mais la carte correspondante ne
    //        s'affiche JAMAIS dans le listing (garde texte réel du renderer) ;
    //      - avis avec texte (nouveau ou DÉJÀ présent) → comment_approved = 0
    //        (validation administrative — le texte ne s'affiche publiquement
    //        QUE SI ET SEULEMENT SI validé par l'admin ; la note associée compte
    //        elle dans la moyenne dès la soumission) ;
    //      - sans contexte commande valide → statu quo anti-spam (0).
    $auto_approve = (!$has_text && !$existing_has_text && $review_order);
    $new_approved = ($has_text || $existing_has_text) ? 0 : ($review_order ? 1 : $existing_approved);

    if ($existing_id) {
        // 4a) Mise à jour de l'avis existant (même commande) : note = max,
        //     texte ajouté/replacé, statut recalculé selon la modération.
        $abaya_update = [
            'comment_ID'      => $existing_id,
            'comment_approved' => $new_approved,
        ];
        if ($has_text) $abaya_update['comment_content'] = $comment;
        wp_update_comment($abaya_update);
        update_comment_meta($existing_id, 'rating', $final_rating);
        update_comment_meta($existing_id, 'reviewer_name', $reviewer_name);
        update_comment_meta($existing_id, 'reviewer_city', $reviewer_city);
        if ($review_offer !== '') update_comment_meta($existing_id, 'review_offer', $review_offer);
        clean_comment_cache($existing_id);
    } else {
        // 4b) Insertion d'un nouvel avis (système natif de commentaires, type
        //     « review »). Le nom de la cliente est stocké dans comment_author
        //     (affichage natif WP), la ville en comment_meta « reviewer_city »,
        //     la commande d'origine en « review_order_id » (règle du maximum).
        $comment_id = wp_insert_comment([
            'comment_post_ID'      => $target_id,
            'comment_author'       => $reviewer_name,
            'comment_author_email' => '',
            'comment_author_url'   => '',
            'comment_content'      => $has_text ? $comment : 'تقييم بنقرة واحدة',
            'comment_type'         => 'review',
            'comment_approved'     => $auto_approve ? 1 : 0,
            'comment_meta'         => [
                'rating'          => $final_rating,
                'reviewer_name'   => $reviewer_name,
                'reviewer_city'   => $reviewer_city,
                'review_offer'    => $review_offer,
                'review_order_id' => $review_order ? (string) $review_order->get_id() : '',
            ],
        ]);
        if (!$comment_id || is_wp_error($comment_id)) {
            return new WP_Error('abaya_review_failed', 'خطأ في الخدمة. يرجى المحاولة لاحقاً.', ['status' => 500]);
        }
    }

    // 5) Moyenne recalculée depuis la base (toutes les notes, indépendamment de
    //    la modération) : renvoyée au client pour mise à jour immédiate du badge
    //    sous le H1 — c'est la même source que le rendu serveur du badge.
    $abaya_summary = abaya_review_summary();

    // 6) Réponse : message conforme au flux réel (auto-approbation / modération texte /
    //    en attente anti-spam sans contexte commande valide).
    return rest_ensure_response([
        'success'    => true,
        'moderation' => $has_text,
        'rating'     => $final_rating,
        'average'    => round((float) $abaya_summary['average'], 2),
        'count'      => (int) $abaya_summary['count'],
        'message'    => $has_text
            ? 'شكراً جزيلاً! تم استلام رأيك وسيُنشر بعد المراجعة.'
            : ($auto_approve ? 'شكراً لك! تم تسجيل تقييمك بنجاح'
                             : 'شكراً لك! تم استلام تقييمك وسيُنشر بعد المراجعة.'),
    ]);
}

/* ---------- Enregistrement des routes REST (méthode POST) ---------- */
add_action('rest_api_init', function () {
    register_rest_route('landing/v1', '/create-order', [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'abaya_handle_create_order',
        'permission_callback' => '__return_true',
    ]);
    register_rest_route('landing/v1', '/add-to-order', [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'abaya_handle_add_to_order',
        'permission_callback' => '__return_true',
    ]);
    register_rest_route('landing/v1', '/submit-review', [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'abaya_handle_submit_review',
        'permission_callback' => '__return_true',
    ]);
});

/* ---------- Création automatique des pages (une seule fois, sans écraser l'existant) ---------- */
function abaya_ensure_pages() {
    if (get_option('abaya_pages_version') === ABAYA_PAGES_VERSION) return;

    if (!get_option('permalink_structure')) {
        update_option('permalink_structure', '/%postname%/');
    }

    foreach (abaya_pages() as $slug => $p) {
        if (get_page_by_path($slug)) continue; // ne jamais écraser une page existante
        $file = get_template_directory() . '/pages/' . $slug . '.html';
        wp_insert_post([
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_name'    => $slug,
            'post_title'   => $p[0],
            'post_content' => file_exists($file) ? file_get_contents($file) : '',
        ]);
    }

    /* Mandat multi-landing : une page WordPress par offre secondaire du registre,
     * servie par le GABARIT UNIQUE page-offer.php (aucun fichier PHP par offre).
     * L'offre vedette est exclue (elle est servie à la racine par front-page.php).
     * Slugs réservés (pages légales, merci, wp-*) exclus par construction :
     * le registre ne contient que des slugs d'offres. */
    $abaya_front = defined('ABAYA_FRONT_OFFER') ? ABAYA_FRONT_OFFER : 'accueil';
    $abaya_reserved = array_merge(array_keys(abaya_pages()), ['merci', 'www', 'wp-admin', 'wp-login.php', 'wp-content', 'wp-includes']);
    foreach (abaya_offers() as $abaya_offer_slug => $abaya_offer_entry) {
        if ($abaya_offer_slug === $abaya_front) continue;
        if (in_array($abaya_offer_slug, $abaya_reserved, true)) continue;
        if (get_page_by_path($abaya_offer_slug)) continue; // ne jamais écraser
        $abaya_page_id = wp_insert_post([
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_name'    => $abaya_offer_slug,
            'post_title'   => $abaya_offer_entry['name'],
            'post_content' => '',
        ]);
        if ($abaya_page_id && !is_wp_error($abaya_page_id)) {
            update_post_meta($abaya_page_id, '_wp_page_template', 'page-offer.php');
        }
    }

    update_option('abaya_pages_version', ABAYA_PAGES_VERSION);
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'abaya_ensure_pages');
add_action('admin_init', 'abaya_ensure_pages'); // couvre le cas d'une mise à jour du thème déjà actif

/* ---------- v3.20 (mandat 4P LP « design pages réglementaires ») : suppression
 * DÉFINITIVE des émojis des pages réglementaires en base de données ----------
 * Les pages sont semées UNE SEULE FOIS par abaya_ensure_pages() (« ne jamais
 * écraser ») : nettoyer les fichiers pages/*.html + inc-pages.php ne suffit donc
 * pas pour la production. Ce sync one-time retire UNIQUEMENT les codepoints
 * émojis (audit docs/AUDIT-LEGAL-DESIGN-v3.20.md §2/§4) du post_title et du
 * post_content des 7 pages réglementaires — aucun autre caractère n'est touché,
 * la page transactionnelle « merci » et toute donnée non-émoji sont préservées. */
function abaya_legal_emoji_pattern() {
    return '/[\x{2300}-\x{23FF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{1F000}-\x{1FAFF}\x{FE0F}\x{200D}]/u';
}

function abaya_strip_legal_page_emojis() {
    if (get_option('abaya_legal_emoji_sync') === 'v1') return; // one-time

    $abaya_pattern = abaya_legal_emoji_pattern();
    foreach (abaya_pages() as $abaya_slug => $abaya_p) {
        if ($abaya_slug === 'merci') continue; // page transactionnelle hors périmètre
        $abaya_page = get_page_by_path($abaya_slug);
        if (!$abaya_page) continue;

        $abaya_update = ['ID' => $abaya_page->ID];
        $abaya_title  = preg_replace($abaya_pattern, '', (string) $abaya_page->post_title);
        if ($abaya_title !== null && $abaya_title !== (string) $abaya_page->post_title) {
            $abaya_update['post_title'] = trim($abaya_title);
        }
        $abaya_content = preg_replace($abaya_pattern, '', (string) $abaya_page->post_content);
        if ($abaya_content !== null && $abaya_content !== (string) $abaya_page->post_content) {
            $abaya_update['post_content'] = $abaya_content;
        }
        if (count($abaya_update) > 1) wp_update_post($abaya_update); // jamais d'écriture sur échec regex (null)
    }
    update_option('abaya_legal_emoji_sync', 'v1');
}
add_action('init', 'abaya_strip_legal_page_emojis');        // front : effectif dès le déploiement
add_action('admin_init', 'abaya_strip_legal_page_emojis');  // admin : WP Pusher / wp-admin

/* ---------- Routage multi-landing (mandat : gabarit unique alimenté par le registre) ---------- */

/* Enregistre « Landing Offre » dans la liste des gabarits de page (admin). */
add_filter('theme_page_templates', function ($templates) {
    $templates['page-offer.php'] = 'Landing Offre';
    return $templates;
});

/* Garantie de résolution : toute page dont le slug est une offre du registre
 * est TOUJOURS servie par le gabarit unique page-offer.php (indépendamment
 * de la méta _wp_page_template). Les pages légales, /merci/ et wp-* gardent
 * leur résolution native. Racine = front-page.php (offre vedette), canonical
 * géré nativement par WordPress (permalink de chaque page). */
add_filter('template_include', function ($template) {
    if (is_page()) {
        $abaya_slug = (string) get_post_field('post_name', get_queried_object_id());
        if (isset(abaya_offers()[$abaya_slug])) {
            $abaya_offer_template = get_template_directory() . '/page-offer.php';
            if (file_exists($abaya_offer_template)) return $abaya_offer_template;
        }
    }
    return $template;
}, 99);

/* ---------- Badge étoiles sous le H1 (toutes les landings — mandat 4P LP) ---------- */

/** Moyenne calculée dynamiquement depuis la base — DÉCOUPLÉE de la modération
 *  (mandat « fix/rating-comments-decoupling ») : le score global sous le H1
 *  agrège TOUTES les notes de type « review » (approuvées ET en attente),
 *  indépendamment du corps du commentaire. Chaque note attribuée met à jour la
 *  moyenne générale immédiatement. Union déterministe approve+hold : le spam et
 *  la corbeille restent exclus par construction (comment_approved 0 ou 1 seul). */
function abaya_review_summary() {
    $post_id = abaya_reviews_target_post_id();
    if (!$post_id) return ['count' => 0, 'average' => 0];
    $reviews = array_merge(
        get_comments(['status' => 'approve', 'type' => 'review', 'post_id' => $post_id, 'number' => 0, 'fields' => 'ids']),
        get_comments(['status' => 'hold', 'type' => 'review', 'post_id' => $post_id, 'number' => 0, 'fields' => 'ids'])
    );
    $sum = 0;
    $count = 0;
    foreach ($reviews as $review_id) {
        $rating = (int) get_comment_meta($review_id, 'rating', true);
        if ($rating < 1 || $rating > 5) continue;
        $sum += $rating;
        $count++;
    }
    return ['count' => $count, 'average' => $count ? $sum / $count : 0];
}

/** Badge étoiles sous le H1 (mandat 4P LP « Ajustement Final & Badge Étoiles ») :
 *  5 étoiles SVG au FORMAT EXACT du composant de la section avis
 *  (#avis-clients .review-card-stars svg : même <path>, même viewBox="0 0 24 24",
 *  classes .review-star / .review-star.is-empty identiques) — garnies
 *  dynamiquement selon la moyenne calculée depuis la base de données
 *  (toutes les notes « review », indépendamment de la modération du texte) :
 *  couche grise + couche dorée clippée à --rating-fill % (fraction de la
 *  moyenne, ex. 4.9/5 → 98 %).
 *  ISOLATION : seul le markup SVG est réutilisé — aucun texte, aucune carte
 *  ni formulaire de la section avis n'est dupliqué. Rendu sur la landing
 *  principale ET les 3 landings secondaires. */
function abaya_render_rating_summary() {
    $summary = abaya_review_summary();
    $average = (float) $summary['average'];
    $score   = number_format($average, 1, '.', '');
    $fill    = $average / 5 * 100;
    /* Markup SVG : copie CONFORME de l'étoile de la section avis (functions.php
     * abaya_render_review_card) — path et viewBox identiques au caractère près. */
    $abaya_star_svg = '<svg class="review-star" viewBox="0 0 24 24" aria-hidden="true"><path d="m12 2.5 2.9 5.9 6.5.9-4.7 4.6 1.1 6.5-5.8-3.1-5.8 3.1 1.1-6.5-4.7-4.6 6.5-.9z"/></svg>';
    $abaya_star_empty = str_replace('class="review-star"', 'class="review-star is-empty"', $abaya_star_svg);
    echo '<div class="landing-rating" role="img" data-average="' . esc_attr($average) . '" data-count="' . esc_attr($summary['count']) . '" aria-label="' . esc_attr($score) . ' / 5">';
    echo '<span class="landing-rating-stars" dir="ltr" style="--rating-fill:' . esc_attr($fill) . '%" aria-hidden="true">';
    echo '<span class="landing-rating-layer">' . str_repeat($abaya_star_empty, 5) . '</span>';
    echo '<span class="landing-rating-layer landing-rating-fill">' . str_repeat($abaya_star_svg, 5) . '</span>';
    echo '</span>';
    echo '<strong class="landing-rating-score">' . esc_html($score) . ' / 5</strong>';
    echo '<span class="landing-rating-count">(' . esc_html(number_format_i18n($summary['count'])) . ' تقييم)</span>';
    echo '</div>';
}

/* Feuille de style des composants offres (badge étoiles SVG sous le H1, pop-up
 * évaluation 1-clic, cross-sell /merci/, code promo). Chargée sur TOUTES les
 * landings (racine + secondaires — mandat 4P LP : badge étoiles sur la landing
 * principale également) ainsi que sur la page /merci/. */
add_action('wp_enqueue_scripts', function () {
    if (is_page('merci') || abaya_is_landing_page()) {
        wp_enqueue_style('abaya-offers', get_template_directory_uri() . '/assets/css/offers.css', [], abaya_asset_ver('assets/css/offers.css'));
    }
}, 20);

