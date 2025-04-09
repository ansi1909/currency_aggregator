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

// Anunciar nuestro nuevo agregador
add_filter('woocs_announce_aggregator', function($aggregators){
    $aggregators['pydolarve_bcv'] = 'PyDolarVE BCV';
    return $aggregators;
});

// Procesamiento de tasas
add_filter('woocs_add_aggregator_processor', function($aggregator_key, $currency_name){
    global $WOOCS;
    $request = 0;

    // Verificamos si es el agregador personalizado
    if ($aggregator_key === 'pydolarve_bcv') {
        // URL de la API
        $query_url = 'https://pydolarve.org/api/v1/dollar?monitor=bcv';
        
        // Obtenemos el contenido
        if (function_exists('curl_init')) {
            $res = $WOOCS->file_get_contents_curl($query_url);
        } else {
            $res = @file_get_contents($query_url);
        }

        // Convertimos a objeto PHP
        $data = json_decode($res, true);

        // Verificamos y asignamos la tasa
        if (isset($data['price'])) {
            // Supongamos que $WOOCS->default_currency es USD y convertimos a VES
            if ($WOOCS->default_currency === 'USD' && $currency_name === 'VES') {
                $request = floatval($data['price']);
            } else {
                
                // Por defecto, devolvemos la tasa en cero si no corresponde a VES
                $request = 0;
            }
        } else {
            $request = sprintf("No hay datos disponibles para %s", $currency_name);
        }
    }

    return $request;
}, 10, 2);