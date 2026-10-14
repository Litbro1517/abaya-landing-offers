<?php
/**
 * Template Name: Landing Offre
 *
 * GABARIT UNIQUE des landing pages secondaires (mandat multi-landing) :
 * une seule page template pour toutes les offres — l'offre est résolue
 * depuis le slug de la page (registre abaya_offers(), inc-offers.php).
 * Ajouter une offre = ajouter une entrée au registre, AUCUN fichier PHP.
 */
if (!defined('ABSPATH')) exit;
$GLOBALS['abaya_offer_id'] = (string) get_post_field('post_name', get_queried_object_id());
if (!isset(abaya_offers()[$GLOBALS['abaya_offer_id']])) {
    /* Slug hors registre : repli sûr sur l'offre vedette. */
    $GLOBALS['abaya_offer_id'] = defined('ABAYA_FRONT_OFFER') ? ABAYA_FRONT_OFFER : 'accueil';
}
get_template_part('template-parts/landing-offer');
