<?php if (!defined('ABSPATH')) exit;
$u = esc_url(get_template_directory_uri());
/* Tunnel post-achat (mandat « fix/post-purchase-navigation-flow ») :
 *  - landings de vente (vedette + secondaires) et page /merci/ : logo
 *    STRICTEMENT non-cliquable et AUCUN lien de retour vers l'accueil —
 *    isolation hermétique du tunnel post-achat. Sur une landing secondaire
 *    déverrouillée depuis la Thank You Page, la flèche de retour explicite
 *    vise la Thank You Page personnalisée (jamais l'offre principale) ;
 *  - pages légales (hors tunnel) : navigation historique conservée. */
$abaya_tunnel_page = function_exists('abaya_is_landing_page') ? (abaya_is_landing_page() || is_page('merci')) : false;
$abaya_upsell_ctx  = function_exists('abaya_upsell_context') ? abaya_upsell_context() : false;
?>
<header class="legal-header">
  <div class="logo-wrapper">
<?php if ($abaya_tunnel_page) : ?>
    <span class="footer-wordmark"><img src="<?= $u ?>/assets/images/logo-header-color.svg" alt="Abaya Collection" class="header-logo-svg" /></span>
<?php else : ?>
    <a class="footer-wordmark" href="/" aria-label="Accueil Abaya Collection"><img src="<?= $u ?>/assets/images/logo-header-color.svg" alt="Abaya Collection" class="header-logo-svg" /></a>
<?php endif; ?>
  </div>
<?php if ($abaya_upsell_ctx) : ?>
<?php /* Tunnel post-achat : flèche de retour explicite en haut de page — cible la
       Thank You Page PERSONNALISÉE de la commande (duo order+key porté par
       le lien), jamais la landing principale. DESTINATION INCHANGÉE (merci_url).
       v3.24 (mandat « fix/unified-back-link ») : libellé unifié court « ‹ رجوع »
       (formule universelle desktop+mobile, identique aux pages réglementaires) —
       le long libellé historique (4 mots) empilait la pilule en cercle
       déformé sur mobile. Sens de la flèche : le glyphe ‹ (bidi miroir) rend
       orienté vers la droite en RTL, comme la flèche SVG historique. */ ?>
  <a class="upsell-back-btn" href="<?php echo esc_url($abaya_upsell_ctx['merci_url']); ?>">
    <span class="back-arrow" aria-hidden="true">‹</span> <span class="back-text">رجوع</span>
  </a>
<?php elseif (!$abaya_tunnel_page) : ?>
<?php /* v3.24 : pages réglementaires — même formule unifiée « ‹ رجوع » avec
       flèche indicative (absente avant) ; destination ACCUEIL inchangée. */ ?>
  <a class="legal-home-link" href="/"><span class="back-arrow" aria-hidden="true">‹</span> <span class="back-text">رجوع</span></a>
<?php endif; ?>
</header>
