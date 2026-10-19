<?php if (!defined('ABSPATH')) exit;
$u = esc_url(get_template_directory_uri());
?>
<div class="announcement">
      التوصيل مجاني والدفع عند الاستلام في جميع مدن المغرب
      <span aria-hidden="true">🇲🇦</span>
    </div>

    <header class="site-header">
<?php /* Tunnel post-achat (mandat ⑭) : racine ciblée par la carte upsell de
       l'offre vedette (commande tunnel valide dans l'URL) → flèche de retour
       Thank You Page en haut de page. Rendu par défaut (sans duo) : inchangé. */ ?>
<?php $abaya_upsell_ctx = function_exists('abaya_upsell_context') ? abaya_upsell_context() : false; ?>
<?php if ($abaya_upsell_ctx) : ?>
      <div class="upsell-back-bar"><a class="upsell-back-btn" href="<?php echo esc_url($abaya_upsell_ctx['merci_url']); ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg><span>العودة إلى صفحة التأكيد</span></a></div>
<?php endif; ?>
      <div class="logo-wrapper">
<?php /* Tunnel post-achat (mandat « fix/post-purchase-navigation-flow ») :
       le site fonctionne sur un modèle de Landing Pages ciblées — le logo de
       l'en-tête est STRICTEMENT non-cliquable sur toutes les landing pages de
       vente (aucun lien hypertexte vers l'accueil). La classe .wordmark est
       conservée : styles et rendu inchangés bit-à-bit. */ ?>
        <span class="wordmark">
          <img src="<?= $u ?>/assets/images/logo-header-color.svg" alt="Abaya Collection" class="header-logo-svg" />
        </span>
      </div>
    </header>
