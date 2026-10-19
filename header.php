<?php if (!defined('ABSPATH')) exit; ?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="theme-color" content="#f8f6f2" />
<?php $abaya_desc = abaya_meta_description(); if ($abaya_desc) : ?>
<meta name="description" content="<?= esc_attr($abaya_desc) ?>" />
<?php endif; ?>
<?php wp_head(); ?>
</head>
<?php /* v3.6 (mandat 4P « Uniformisation LP secondaires ») : la classe
 * is-upsell est RETIRÉE — les landings secondaires sont des copies conformes
 * de la LP principale (même charte VF v3.0-v3.5) ; les règles
 * :not(.is-upsell) de styles.css s'appliquent désormais partout (scopages
 * inertes) et l'ancienne inversion dorée v2.9 est supprimée de styles.css.
 * Les spécificités tunnel (barre de retour, bandeau, pré-remplissage) sont
 * portées par le contexte PHP abaya_upsell_context(), plus par le CSS. */ ?>
<body class="<?= abaya_is_landing_page() ? 'product-landing' : 'legal-page' ?>">
<?php wp_body_open(); ?>
<?php get_template_part('template-parts/' . (is_front_page() ? 'site-header' : 'legal-header')); ?>
