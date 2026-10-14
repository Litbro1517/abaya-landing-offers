<?php
/**
 * RACINE = OFFRE VEDETTE (constante ABAYA_FRONT_OFFER, inc-offers.php).
 * Le rendu est délégué au gabarit unique template-parts/landing-offer.php,
 * alimenté par le registre abaya_offers() — aucune duplication de markup.
 * Rendu de l'offre vedette : inchangé bit-à-bit par rapport au socle.
 */
if (!defined('ABSPATH')) exit;
$GLOBALS['abaya_offer_id'] = defined('ABAYA_FRONT_OFFER') ? ABAYA_FRONT_OFFER : 'accueil';
get_template_part('template-parts/landing-offer');
