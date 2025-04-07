<?php
/*
Plugin Name: BCV Currency Aggregator (via PyDolarVE Oficial)
Description: Agrega la tasa oficial del BCV al plugin FOX - Currency Switcher, usando la API de pydolarve.org con monitor=bcv.
Version: 1.3
Author: Ansise Segovia
*/

if (!defined('ABSPATH')) {
    exit;
}

// 1. Anunciar el agregador personalizado
add_filter('woocs_announce_aggregator', 'bcv_pydolarve_announce');
function bcv_pydolarve_announce($aggregators) {
    $aggregators['bcvpydolar'] = 'BCV Venezuela (PyDolarVE)';
    return $aggregators;
}

// 2. Registrar el procesador del agregador
add_filter('woocs_add_aggregator_processor', 'bcv_pydolarve_processor', 10, 2);

function bcv_pydolarve_processor($rate, $currency_name) {
    if ($currency_name !== 'bcvpydolar') {
        return $rate;
    }

    $response = wp_remote_get('https://pydolarve.org/api/v1/dollar?monitor=bcv', array(
        'timeout' => 15,
        'headers' => array(
            'User-Agent' => 'Mozilla/5.0 (compatible; WordPress BCV Aggregator)'
        )
    ));

    if (is_wp_error($response)) {
        return $rate; // fallback
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (!isset($data['price'])) {
        return $rate;
    }
    
    return floatval($data['price']);
}