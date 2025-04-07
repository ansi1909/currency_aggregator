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

// 1. Agregar al listado de agregadores en FOX
add_filter('woocs_currency_aggregators', 'bcv_pydolarve_agregador');
function bcv_pydolarve_agregador($aggregators) {
    $aggregators['bcvpydolar'] = array(
        'title' => 'BCV Venezuela (PyDolarVE)',
        'url'   => site_url('/wp-json/bcv-api/tasa'),
    );
    return $aggregators;
}

// 2. Crear el endpoint que usa la API de PyDolarVE con monitor=bcv
add_action('rest_api_init', function () {
    register_rest_route('bcv-api', '/tasa', array(
        'methods'  => 'GET',
        'callback' => 'bcv_pydolarve_obtener_tasa',
        'permission_callback' => '__return_true',
    ));
});

function bcv_pydolarve_obtener_tasa() {
    $response = wp_remote_get('https://pydolarve.org/api/v1/dollar?monitor=bcv', array(
        'timeout' => 15,
        'headers' => array(
            'User-Agent' => 'Mozilla/5.0 (compatible; WordPress BCV Aggregator)'
        )
    ));

    if (is_wp_error($response)) {
        return new WP_Error('error_api', 'No se pudo acceder a la API de PyDolarVE', array('status' => 500));
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (!isset($data['price'])) {
        return new WP_Error('parse_error', 'No se encontró la tasa BCV en la respuesta de PyDolarVE', array('status' => 500));
    }

    return array(
        'USD' => floatval($data['price'])
    );
}
