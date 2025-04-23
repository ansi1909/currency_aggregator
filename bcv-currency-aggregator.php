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
        
        $precio_dolar = obtener_dolar_bcv_estable();

        if(isset($precio_dolar)){
            if($WOOCS->default_currency === 'USD' && $currency_name === 'VES'){
                $request = $precio_dolar;
            }
        }

    }

    return $request;
}, 10, 2);

function obtener_dolar_bcv_estable(){
    //Nombre del transient
    $transient_key = 'dolar_bcv_fijo';
    
    //Revisa si ya está guardado el valor
    $valor_guardado = get_transient($transient_key);
    if($valor_guardado != false){
        return $valor_guardado;
    }

    //Fecha y hora actual
    $fecha_actual = new DateTime('now', DateTimeZone('America/Caracas'));
    $hora_actual = (int) $fecha_actual->format('H');
    $dia_semana = (int) $fecha_actual->format('N'); // Lunes = 1, Domingo = 7

    // Si es viernes antes de las 5pm o un día de semana antes de las 5p
    if($dia_semana <= 5 && $hora_actual < 17){
        //Consumir API
        $response = wp_remote_get('https://pydolarve.org/api/v1/dollar?monitor=bcv');

        if(is_wp_error($response)){
            return "Error al conectar con la API";
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if(!isset($data['price'])){
            return 'Respuesta invalida de la API';
        }

        $precio = floatval($data['price']);

        //Guardar por 24 horas, ya que se usará todo el fin de semana si es viernes
        $tiempo_expriracion = ($dia_semana == 5) ? DAY_IN_SECONDS * 3 : HOUR_IN_SECONDS * (17 - $hora_actual);
        set_transient($transient_key, $precio, $tiempo_expriracion);

        return $precio;
    }
    // Si es después de las 5pm, sábado o domingo, devolver el valor fijo anterior
    // Si aún no hay valor fijo (por error o primera ejecución), forzar uso de la API
    if($valor_guardado == false){

        $response = wp_remote_get('https://pydolarve.org/api/v1/dollar?monitor=bcv');

        if(is_wp_error($response)){
            return "Error al conectar con la API";
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
            
        if(!isset($data['price'])){
            return 'Respuesta invalida de la API';
        }
            
        $precio = floatval($data['price']);
        set_transient($transient_key, $precio, HOUR_IN_SECONDS * 12); //Evita quedar sin valor
        return $precio;

    }

    return $valor_guardado;
    
}

